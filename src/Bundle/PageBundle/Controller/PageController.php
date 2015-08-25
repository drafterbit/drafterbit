<?php

namespace Drafterbit\Bundle\PageBundle\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Drafterbit\Bundle\PageBundle\Form\Type\PageType;
use Drafterbit\Bundle\PageBundle\Entity\Page;

/**
 * @Route("/%admin%")
 */
class PageController extends Controller
{
    /**
     * @Route("/page", name="dt_page")
     * @Template()
     * @Security("is_granted('ROLE_PAGE_VIEW')")
     */
    public function indexAction(Request $request)
    {
        $viewId = 'page';

        if($action = $request->request->get('action')) {

            // safety first
            $token = $request->request->get('_token');
            if(!$this->isCsrfTokenValid($viewId, $token)) {
                throw $this->createAccessDeniedException();
            }

            $posts = $request->request->get('pages');

            if(!$posts) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $this->get('translator')->trans('Please make selection first')
                ]);
            }

            $em = $this->getDoctrine()->getManager();

             foreach ($posts as $id) {
                $post = $em->getRepository('PageBundle:Page')->find($id);

                switch ($action) {
                    case 'trash':
                        $post->setDeletedAt(new \DateTime());
                        $status = 'warning';
                        $message = 'Post(s) moved to trash';
                        $em->persist($post);
                        break;
                    case 'restore':
                        $post->setDeletedAt(NULL);
                        $status = 'success';
                        $message = 'Post(s) restored';
                        $em->persist($post);
                        break;
                    case 'delete':
                        $em->remove($post);

                        $status = 'success';
                        $message = 'Post(s) deleted permanently';
                        break;
                    default:
                        break;
                }

                $em->flush();
            }

            return new JsonResponse([
                'status' => $status,
                'message' => $this->get('translator')->trans($message),
                ]);
        }

        return [
            'view_id' => $viewId,
            'page_title' => $this->get('translator')->trans('Page')
        ];
    }

    /**
     * @Route("/page/data/{status}", name="dt_page_data")
     */
    public function data($status)
    {
        $pagesArr  = [];
        $query = $this->getDoctrine()
            ->getManager()
            ->getRepository('PageBundle:Page')
            ->createQueryBuilder('p');

        if($status == 'trashed') {
            $query->where("p.deletedAt is not null");
        } else {
            $query->where("p.deletedAt is null");
            switch ($status) {
                case 'all':
                    break;
                case 'published':
                    $query->andWhere('p.status = 1');
                    break;
                case 'pending':
                    $query->andWhere('p.status = 0');
                    break;
                default:
                    break;
            }
        }

        $pages = $query->getQuery()->getResult();

        $pagesArr  = [];
        foreach ($pages as $page) {

            $data = [];
            $data[] = $page->getId();
            $data[] = $page->getTitle();
            $data[] = $page->getUpdatedAt()->format('d F Y H:i');

            $pagesArr[] = $data;
        }

        $ob = new \StdClass;
        $ob->data = $pagesArr;
        $ob->recordsTotal= count($pagesArr);
        $ob->recordsFiltered = count($pagesArr);

        return new JsonResponse($ob);
    }

    /**
     * @Route("/page/edit/{id}", name="dt_page_edit")
     * @Template()
     * @todo crate permission attr constant
     * @Security("is_granted('ROLE_PAGE_EDIT')")
     */
    public function editAction($id)
    {
        $pageTitle = 'Edit Page';
        $page = $this->getDoctrine()
            ->getManager()
            ->getRepository('PageBundle:Page')
            ->find($id);

        if(!$page and ($id != 'new')) {
            throw  $this->createNotFoundException();
        }

        if(!$page) {
            $page = new Page();
            $pageTitle = 'New Page';
        }

        $layoutOptions = $this->getLayoutOptions();
        $form = $this->createForm(new PageType($layoutOptions), $page);
        $form->get('id')->setData($id);

        return [
            'form' => $form->createView(),
            'view_id' => 'page-edit',
            'page_id' => $id,
            'action' =>  $this->generateUrl('dt_page_save'),
            'page_title' => $this->get('translator')->trans($pageTitle)
        ];
    }

    /**
     * @Route("/page/save", name="dt_page_save")
     */
    public function saveAction(Request $request)
    {
        $requestPage = $request->request->get('page');
        $id = $requestPage['id'];

        $page = $this->getDoctrine()
            ->getManager()
            ->getRepository('PageBundle:Page')
            ->find($id);

        $isNew = false;
        if(!$page) {
            $page = new Page();
            $isNew = true;
        }

        $layoutOptions = $this->getLayoutOptions();
        $form = $this->createForm(new PageType($layoutOptions), $page);
        $form->handleRequest($request);

         if($form->isValid()) {

            //save data to database
            $page = $form->getData();
            $page->setUser($this->getUser());
            $page->setUpdatedAt(new \DateTime);

            if($isNew) {
                $page->setCreatedAt(new \DateTime);
                $page->setDeletedAt(NULL);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($page);
            $em->flush();

            $id = $page->getId();

            // log
            $logger = $this->get('logger');
            $logger->info('%user% edited page %page%', ['user' => $this->getUser()->getId(), 'page' => $id]);

            $response = [
                'message' => $this->get('translator')->trans('Page saved'),
                'status' => 'success',
                'id' => $id];

            $status = 200;
        } else {

            $errors = [];
            $formView = $form->createView();

            // @todo clean this, make a recursive
            // create server, form error extractor maybe
            foreach ($formView as $inputName => $view) {

                if($view->children) {
                    foreach ($view->children as $name => $childView) {
                        if(isset($childView->vars['errors'])) {
                            foreach($childView->vars['errors'] as $error) {
                                $errors[$childView->vars['full_name']] = $error->getMessage();
                            }
                        }
                    }
                }

                if(isset($view->vars['errors'])) {
                    foreach($view->vars['errors'] as $error) {
                        $errors[$view->vars['full_name']] = $error->getMessage();
                    }
                }
            }

            $response['error'] = [
                'type' => 'validation',
                'messages' => $errors
            ];

            $status = 500;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * Get layout options from current layout theme directory
     *
     * @todo handle the view if there is no theme
     * @return array
     */
    private function getLayoutOptions()
    {
        $theme = $this->container->getParameter('theme');
        $themesPath = $this->container->getParameter('themes_path');

        $layouts = [];
        if(is_dir($layoutPath = $themesPath.'/'.$theme.'/_tpl/layout')) {
            $files = (new Finder)->depth(0)
                ->in($layoutPath);
        } else {
            $files = [];
        }

        foreach ($files as $file) {
            $layouts[$file->getfilename()] = $file->getfilename();
        }

        return $layouts;
    }
}
