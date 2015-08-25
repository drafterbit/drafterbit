<?php

namespace Drafterbit\Bundle\SystemBundle\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Drafterbit\Bundle\SystemBundle\Entity\PanelConfig;

/**
 * @Route("%admin%")
 */
class SystemController extends Controller
{
    /**
     * @Route("/", name="dt_system_dashboard")
     * @Template()
     */
    public function dashboardAction()
    {
        $em = $this->getDoctrine()->getManager();

        $i=1;
        foreach ($this->get('dashboard')->getPanels() as $name => $panel) {
            $panelConfig = $em->getRepository('SystemBundle:PanelConfig')
            ->findOneBy(['user' => $this->getUser(), 'name' => $name]);

            if(!$panelConfig) {
                $panelConfig = new PanelConfig();
                $panelConfig->setUser($this->getUser());
                $panelConfig->setName($name);

                $position = ($i%2 == 0 ) ? 'right' : 'left';
                
                $panelConfig->setPosition($position);
                $panelConfig->setSequence($i++);
                $panelConfig->setStatus(1);

                $em->persist($panelConfig);
                $em->flush();
            }

            $panelConfigs[] = $panelConfig;
        }

        $panels = $this->buildPanels($panelConfigs);

        return [
            'panels' => $this->get('dashboard')->getPanels(),
            'left_panels' => $panels['left'],
            'right_panels' => $panels['right'],
            'page_title' => $this->get('translator')->trans('Dashboard')
        ];
    }

    private function buildPanels($panelConfig)
    {
        $panels = [];
        $panels['left'] = [];
        $panels['right'] = [];

        foreach ($panelConfig as $config) {
            $panel = $this->get('dashboard')->getPanel($config->getName());
            $panel->status = $config->getStatus();
            $panels[$config->getPosition()][] = $panel;
        }

        return $panels;
    }

    /**
     * @Route("/system/log", name="dt_system_log")
     * @Template()
     * @Security("is_granted('ROLE_LOG_VIEW')")
     */
    public function logAction(Request $request)
    {
        $viewId = 'log';
        $action = $request->request->get('action');
        $token = $request->request->get('_token');
        $data = [
            'view_id' => $viewId,
            'page_title' => $this->get('translator')->trans('Log')
        ];

        if($action) {
            if(!$this->isCsrfTokenValid($viewId, $token)) {
                throw $this->createAccessDeniedException();
            }

            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('SystemBundle:Log');

            switch ($action) {
                case 'delete':
                    $logIds = $request->request->get('log', []);
                    foreach ($logIds as $id) {
                        $log = $repo->find($id);
                        $em->remove($log);
                    }
                    $message = 'Logs deleted';
                    break;
                case 'clear':
                    $logs = $repo->findAll();
                     foreach ($logs as $log) {
                        $em->remove($log);
                    }
                    $message = 'All logs deleted';
                    break;
                default:
                    break;
            }
            
            $em->flush();
            $data['notif'] = ['message' => $this->get('translator')->trans($message), 'status' => 'success'];
        }

        return $data;
    }

    /**
     * @Route("/system/log/data", name="dt_system_log_data")
     * @Template()
     */
    public function logDataAction()
    {
        $em = $this->getDoctrine()->getManager();
        $logs = $em->getRepository('SystemBundle:Log')->findAll();
        
        $logs = array_reverse($logs);
        $logArr = [];
        foreach ($logs as $log) {
            
            $data = [];
            $data[] = $log->getid();
            $data[] = date('d-m-Y H:i:s', $log->getTime());
            $data[] = $this->get('dt_system.log.display_formatter')->format($log->getMessage(), $log->getContext());

            $logArr[] = $data;
        }

        $ob = new \StdClass;
        $ob->data = $logArr;
        $ob->recordsTotal= count($logArr);
        $ob->recordsFiltered = count($logArr);

        return new jsonResponse($ob);
    }

    /**
     * @Route("/system/cache", name="dt_system_cache")
     * @Template()
     * @Security("is_granted('ROLE_CACHE_VIEW')")
     */
    public function cacheAction(Request $request)
    {
        $notif = false;

        if($message = $this->get('session')->getFlashBag()->get('message')) {
            $status =  $this->get('session')->getFlashBag()->get('status');
            $notif = ['message' => $message[0], 'status' => $status[0]];
        }

        $cacheDir = $this->get('kernel')->getCacheDir();
        $finder = (new Finder)->in($cacheDir)->depth(0);

        $caches = [];

        foreach ($finder as $item) {
            $caches[] = [
                'key' => $item->getFilename(),
                'size' => (filesize($item->getRealPath())/1000).' kb',
            ];
        }

        return [
            'page_title' => $this->get('translator')->trans('Cache'),
            'notif' => $notif,
            'caches' => $caches
        ];
    }

    /**
     * @Route("/system/update", name="dt_system_update")
     * @Template()
     * @todo
     */
    public function updateAction(Request $request)
    {
        return [
            'page_title' => $this->get('translator')->trans('Update'),
            'message' => $this->get('translator')->trans('No update availables.')
        ];
    }

    /**
     * Cache clearer controller
     *
     * @Route("/system/cache/clear", name="dt_system_cache_clear")
     * @Method("POST")
     */
    public function clearCacheAction(Request $request)
    {
        if($this->get('kernel')->getEnvironment() == 'dev') {
            $message = $this->get('translator')
                ->trans('Cache can\'t be cleared from web interface in dev mode');
            $status = 'warning';
        } else {

            $cacheDir = $this->container->getParameter('kernel.cache_dir');
            $filesystem = $this->get('filesystem');
            $this->get('cache_clearer')->clear($cacheDir);
            $filesystem->remove($cacheDir);

            $message = $this->get('translator')->trans('Cache renewed');
            $status = 'success';
        }

        $this->addFlash('status',  $status);
        $this->addFlash('message', $message);
        // Don't user url generation, it will be failed due to cache dir just being cleared
        return new RedirectResponse($request->headers->get('REFERER'));
    }

    /**
     * 
     * @Route("/system/dashboard/sort", name="dt_system_dashboard_sort")
     * @Method("POST")
     */
    public function sortDashboardAction(Request $request) {

        $dashboardPanels = $this->get('dashboard')->getPanels();
        $panels = array_keys($dashboardPanels);

        $order = $request->request->get('order');
        $pos = $request->request->get('pos');

        $order = explode(',', $order);

        $order = array_map(function($el){
            if($part = substr($el, strlen('dashboard-panel-'))) {
                return $part;
            };
        }, $order);

        $em = $this->getDoctrine()->getManager();

        $i = 1;
        foreach ($order as $name) {

            if($name) {

                $panelConfig =  $em->getRepository('SystemBundle:PanelConfig')
                ->findOneBy(['user' => $this->getUser(), 'name' => $name]);

                $panelConfig or $panelConfig = new PanelConfig();

                $status = $panelConfig ? $panelConfig->getStatus() : 1;

                $panelConfig->setUser($this->getUser());
                $panelConfig->setName($name);
                $panelConfig->setPosition($pos);
                $panelConfig->setSequence($i++);

                $em->persist($panelConfig);
                $em->flush();
            }
        }

        return new Response();
    }


    /**
     * 
     * @Route("/system/dashboard/toggle_panel", name="dt_system_dashboard_toggle_panel")
     * @Method("POST")
     */
    public function togglePanelAction(Request $request) {

        $name = $request->request->get('panel');

        $em = $this->getDoctrine()->getManager();
        $panelConfig =  $em->getRepository('SystemBundle:PanelConfig')
            ->findOneBy(['user' => $this->getUser(), 'name' => $name]);

        $panelConfig or $panelConfig = new PanelConfig();

        $status = $panelConfig->getStatus() ? 0 : 1;

        $panelConfig->setUser($this->getUser());
        $panelConfig->setStatus($status);
        $em->persist($panelConfig);
        $em->flush();

        return new Response();
    }
}