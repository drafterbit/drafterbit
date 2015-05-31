<?php

namespace Drafterbit\Bundle\SystemBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Drafterbit\Bundle\SystemBundle\Form\Type\MenuType;
use Drafterbit\Bundle\SystemBundle\Entity\Menu;
use Drafterbit\Bundle\SystemBundle\Entity\MenuItem;

/**
 * @Route("/%admin%")
 */
class MenuController extends Controller
{
    /**
     * @Route("/menu", name="drafterbit_system_menu")
     * @Template("DrafterbitSystemBundle::menu.html.twig")
     * @Security("is_granted('ROLE_MENU_MANAGE')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $menus = $em->getRepository('DrafterbitSystemBundle:Menu')->findAll();

        foreach ($menus as &$menu) {

            $items = $em->getRepository('DrafterbitSystemBundle:MenuItem')->findby(['menu' => $menu]);
            $menu->items = $this->buildFamily($items);
        }

        $form = $this->createForm(new MenuType());

        $pages = $em->getRepository('DrafterbitPageBundle:Page')->findAll();

    	return [
            'form' => $form->createView(),
            'menus' =>  $menus,
    		'page_title' => $this->get('translator')->trans('Menus')
    	];
    }

    private function buildFamily($items, $parent = null)
    {
        $array = [];
        foreach ($items as $item) {
            if($item->getParent() == $parent) {
                $item->childs = $this->buildFamily($items, $item);

                $array[] = $item;
            }
        }

        return $array;
    }

    /**
     * @Route("/menu/save", name="drafterbit_system_menu_save")
     */
    public function saveAction(Request $request)
    {
        $form = $this->createForm(new MenuType(), new Menu);
        $form->handleRequest($request);

         if($form->isValid()) {

            //save data to database
            $menu = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($menu);
            $em->flush();

            $id = $menu->getId();

            // @todo
            // $logger = $this->get('logger');
            // $logger->info(':user:'.$this->getUser()->getId().' edited :user:'.$id, ['id' => $id]);

            $response = [
                'slug' => $menu->getDisplayText(),
                'id' => $id
            ];
        } else {

            $errors = [];
            $formView = $form->createView();

            // @todo clean this, make a recursive
            // create service, FormErrorExtractor maybe
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
        }

        return new JsonResponse($response);   
    }

    /**
     * Actually this is also saving menu if the name changes
     *
     * @Route("/menu/sort", name="drafterbit_system_menu_sort")
     */
    public function sortAction(Request $request)
    {
        $items = $request->request->get('menus');
        $name = $request->request->get('name');
        $id = $request->request->get('id');

        $em = $this->getDoctrine()->getManager();
        $menu = $em->getRepository('DrafterbitSystemBundle:Menu')->find($id);
        $menu->setDisplayText($name);
        $em->persist($menu);
        $em->flush();

        $menuItemRepo = $em->getRepository('DrafterbitSystemBundle:MenuItem');
        if($items) {
            foreach ($items as $data) {

                $parent = $menuItemRepo->find($data['parent']);

                $item = $menuItemRepo->find($data['id']);
                $item->setSequence($data['sequence']);
                $item->setParent($parent);

                $em->persist($item);
                $em->flush();
            }
        }

        return new JsonResponse(['message' => 'Menus '.$name.' saved', 'status' => 'success']);
    }

    /**
     * @Route("/menu/delete", name="drafterbit_system_menu_delete")
     */
    public function deleteAction(Request $request)
    {
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $menu = $em->getRepository('DrafterbitSystemBundle:Menu')->find($id);
        
        $items = $em->getRepository('DrafterbitSystemBundle:MenuItem')
            ->findBy(['menu' => $menu]);

        foreach ($items as $item) {
            $em->remove($item);
        }

        $em->remove($menu);
        $em->flush();

        return new Response();
    }

    /**
     * @Route("/menu/item/add", name="drafterbit_system_menu_item_add")
     */
    public function itemAddAction(Request $request)
    {
        $id = $request->request->get('menu_id');
        $em = $this->getDoctrine()->getManager();
        $menu = $em->getRepository('DrafterbitSystemBundle:Menu')->find($id);

        $item = new MenuItem;
        $item->setDisplayText('New Menu Item');
        $item->setLink('#');
        $item->setMenu($menu);
        $item->setSequence(0);
        $em->persist($item);
        $em->flush();

        return new JsonResponse([
            "label" => $item->getDisplayText(),
            "menu_id" => $item->getMenu()->getId(),
            "parent_id" => 0,
            "link" => "#",
            "id"=> $item->getId(),
        ]);
    }

    /**
     * @Route("/menu/item/delete", name="drafterbit_system_menu_item_delete")
     */
    public function itemDeleteAction(Request $request)
    {
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository('DrafterbitSystemBundle:MenuItem')->find($id);

        $childs = $em->getRepository('DrafterbitSystemBundle:MenuItem')
            ->findBy(['parent' => $item]);

        foreach ($childs as $child) {
            $em->remove($child);
        }

        $em->remove($item);
        $em->flush();

        return new Response();
    }

    /**
     * @Route("/menu/item/save", name="drafterbit_system_menu_item_save")
     */
    public function itemSaveAction(Request $request)
    {
        $id = $request->request->get('id');
        $displayText = $request->request->get('displayText');
        $link = $request->request->get('link');

        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository('DrafterbitSystemBundle:MenuItem')->find($id);
        $item->setDisplayText($displayText);
        $item->setLink($link);

        $errors = $this->get('validator')->validate($item);

        if((boolean) count($errors)) {
            $messages = [];

            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            $response = [
                'error' => [
                    'type' => 'validation',
                    'messages' => $messages
                ]
            ];
        }else {

            $em->persist($item);
            $em->flush();

            $response = [
                'message' => 'Menu item saved',
                'id' => $id,
            ];
        }

        return new JsonResponse($response);
    }
}