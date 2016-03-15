<?php

namespace AppBundle\Controller;

/**
 * Class WorkOrderImageController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
use AppBundle\Form\OrdenTrabajoImagenType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Vallas\ModelBundle\Entity\Imagen;

/**
 * Imagen controller.
 *
 * @Route("/{_locale}/work-order/images", defaults={"_locale"="en"})
 */
class WorkOrderImageController extends VallasAdminController {

    /**
     * @Route("/{type}", name="work_order_img_list")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $qbImage = $em->getRepository('VallasModelBundle:Imagen')->getAllQueryBuilder()->leftJoin('p.orden_trabajo', 'orden_trabajo');
        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, $request->query->getInt('page', 1), 1);
        $imgPaged->setUsedRoute('work_order_img_list');

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        $form = $this->createForm(new OrdenTrabajoImagenType(), $firstImg);

        return $this->render('AppBundle:screens/work_order_img:list.html.twig', array(
            'image' => $firstImg,
            'type' => $type,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="work_order_img_add")
     * @Method("GET")
     */
    public function addAction()
    {
        $entity = new Imagen();
        $entity->setPais($this->getSessionCountry());
        $form = $this->createForm(new OrdenTrabajoImagenType(), $entity);

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                $post = $this->postVar('work_order_img');
                $uploadable_manager = $this->get('esocial_util.form.manager.uploadable_file');
                $imagenUpload = $uploadable_manager->processUploadedFile($form->get('url'), $post['url'], array_key_exists('entity', $params_original) && $params_original['entity'] ? $params_original['entity']->getUrl() : null);

                $em->persist($entity);
                $em->flush();

                $this->get('session')->getFlashBag()->add('notice', $this->get('translator')->trans('form.notice.saved_success'));

                return true;

            }else{
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('form.notice.saved_error'));
                return false;
            }
        }

    }

    /**
     * @Route("/create", name="work_order_img_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new Imagen();
        $entity->setPais($this->getSessionCountry());
        $form = $this->createForm(new OrdenTrabajoImagenType(), $entity);
        $params_original = array('entity' => null);

        if ($request->getMethod() == 'POST'){

            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved){
                return $this->redirect($this->generateUrl('work_order_img_edit', array('id' => $entity->getToken())));
            }

        }

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }

    /**
     * @Route("/{id}/edit", name="work_order_img_edit")
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Imagen')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new OrdenTrabajoImagenType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/{id}/update", name="work_order_img_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Imagen')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $this->createForm(new OrdenTrabajoImagenType(), $entity);
        $params_original = array('entity' => clone $entity);

        if ($request->getMethod() == 'POST'){
            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved){
                return $this->redirect($this->generateUrl('work_order_img_edit', array('id' => $entity->getToken())));
            }
        }

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }
}