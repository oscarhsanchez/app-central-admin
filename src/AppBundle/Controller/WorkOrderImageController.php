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
 * @Route("/{_locale}/work-order-images", defaults={"_locale"="en"})
 */
class WorkOrderImageController extends VallasAdminController {

    /**
     * @Route("/{type}", name="work_order_img_list")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $token = $this->getVar('id');

        $ordenTrabajo = null;
        $qbImage = $em->getRepository('VallasModelBundle:Imagen')->getAllQueryBuilder()->leftJoin('p.orden_trabajo', 'orden_trabajo');
        $qbImage->addOrderBy('p.created_at', 'DESC');

        if ($token){
            $ordenTrabajo = $token ? $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($token) : null;
            if ($ordenTrabajo){
                $qbImage->andWhere('p.orden_trabajo = :ot')->setParameter('ot', $ordenTrabajo->getPkOrdenTrabajo());
            }
        }

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, $request->query->getInt('page', 1), 1);
        $imgPaged->setUsedRoute('work_order_img_list');
        $imgPaged->setParam('type', $type);
        if ($token) $imgPaged->setParam('id', $token);

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/work_order_img:list.html.twig', array(
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'type' => $type,
            'formImage' => $this->createForm(new OrdenTrabajoImagenType(), $firstImg)->createView(),
            'entity' => $ordenTrabajo
        ));
    }

    /**
     * @Route("/{id}/add", name="work_order_img_add")
     * @Method("GET")
     */
    public function addAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
        }

        $entity = new Imagen();
        $entity->setOrdenTrabajo($ot);
        $entity->setPais($this->getSessionCountry());
        $form = $this->createForm(new OrdenTrabajoImagenType(array('_form_name' => 'work_order_img_popup')), $entity);

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

                $post = $this->postVar($form->getName());
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
     * @Route("/{id}/create", name="work_order_img_create")
     * @Method("POST")
     */
    public function createAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
        }

        $entity = new Imagen();
        $entity->setPais($this->getSessionCountry());
        $entity->setOrdenTrabajo($ot);

        $form = $this->createForm(new OrdenTrabajoImagenType(array('_form_name' => 'work_order_img_popup')), $entity);
        $params_original = array('entity' => null);

        if ($request->getMethod() == 'POST'){

            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved){
                return $this->redirect($this->generateUrl('work_order_img_edit', array('id' => $entity->getToken(), 'isPopup' => 1)));
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
        $isPopup = $this->getVar('isPopup');

        $entity = $em->getRepository('VallasModelBundle:Imagen')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createForm(new OrdenTrabajoImagenType(array('_form_name' => 'work_order_img_popup')), $entity) :
                                    $this->createForm(new OrdenTrabajoImagenType(), $entity);

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
            'isPopup' => $isPopup
        ));
    }

    /**
     * @Route("/{id}/update", name="work_order_img_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {
        $origin = $this->getVar('origin');
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Imagen')->getOneByToken($id);
        $isPopup = $this->getVar('isPopup');

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createForm(new OrdenTrabajoImagenType(array('_form_name' => 'work_order_img_popup')), $entity) :
                                    $this->createForm(new OrdenTrabajoImagenType(), $entity);

        $params_original = array('entity' => clone $entity);

        if ($request->getMethod() == 'POST'){
            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved && $origin != 'list'){

                return $this->redirect($this->generateUrl('work_order_img_edit', array('id' => $entity->getToken(), 'isPopup' => $isPopup)));
            }
        }

        if ($origin == 'list'){
            return $this->render('AppBundle:screens/work_order_img:form_list.html.twig', array(
                'form' => $this->createForm(new OrdenTrabajoImagenType(), $entity)->createView(),
                'entity' => $entity
            ));
        }

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }
}