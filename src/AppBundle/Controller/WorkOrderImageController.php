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
use Vallas\ModelBundle\Entity\OrdenTrabajo;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Imagen controller.
 *
 * @Route("/{_locale}/work-order-images/{type}", defaults={"_locale"="es"})
 */
class WorkOrderImageController extends VallasAdminController {

    /**
     * @Route("/", name="work_order_img_list", options={"expose"=true})
     * @RequiresPermission(submodule="work_order_{type}", permissions="R")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $token = $this->getVar('id');
        $estado_imagen = $this->getVar('estado_imagen');

        $ordenTrabajo = null;
        $qbImage = $em->getRepository('VallasModelBundle:Imagen')->getAllQueryBuilder()
            ->leftJoin('p.orden_trabajo', 'ot')
            ->andWhere('ot.tipo = :tipo')->setParameter('tipo', $this->getCodeTypeByUrlType($type))
            ->addOrderBy('p.created_at', 'DESC');

        if ($token){
            $ordenTrabajo = $token ? $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($token) : null;
            if ($ordenTrabajo){
                $qbImage->andWhere('p.orden_trabajo = :ot')->setParameter('ot', $ordenTrabajo->getPkOrdenTrabajo());
            }
        }

        if ($estado_imagen !== null){
            $qbImage->andWhere('p.estado_imagen = :estado_imagen')->setParameter('estado_imagen', $estado_imagen);
        }

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, $request->query->getInt('page', 1), 1);
        $imgPaged->setUsedRoute('work_order_img_list');
        $imgPaged->setParam('type', $type);
        if ($token) $imgPaged->setParam('id', $token);
        if ($estado_imagen) $imgPaged->setParam('estado_imagen', $estado_imagen);

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/work_order_img:list.html.twig', array(
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'type' => $type,
            'formImage' => $this->createForm('AppBundle\Form\OrdenTrabajoImagenType', $firstImg)->createView(),
            'entity' => $ordenTrabajo,
            'estado_imagen' => $estado_imagen,
            'imgValidation' => !$token
        ));
    }

    private function getCodeTypeByUrlType($type){
        switch($type){
            case 'fixing': return '0';
            case 'monitoring': return '1';
            case 'installation': return '2';
            case 'lighting': return '3';
        }
        return '';
    }

    /**
     * @Route("/{id}/add", name="work_order_img_add")
     * @RequiresPermission(submodule="work_order_{type}", permissions="U")
     * @Method("GET")
     */
    public function addAction($id, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
        }

        $entity = new Imagen();
        $entity->setOrdenTrabajo($ot);
        $entity->setPais($this->getSessionCountry());
        $form = $this->createNamedForm('work_order_img_popup', 'AppBundle\Form\OrdenTrabajoImagenType', $entity, array('is_popup' => true));

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'type' => $type,
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
                $imagenUpload = $uploadable_manager->processUploadedFile($form->get('nombre'), $post['nombre'], array_key_exists('entity', $params_original) && $params_original['entity'] ? $params_original['entity']->getNombre() : null);

                if ($imagenUpload) {
                    $pathinfo = pathinfo($imagenUpload);
                    $entity->setPath($pathinfo['dirname'] . '/');
                    $entity->setUrl($request->getSchemeAndHttpHost() . '/media/orden_trabajo_imagen/');
                }

                if (array_key_exists('entity', $params_original) && $params_original['entity']){
                    if ($entity->getEstadoImagen() == 4 && $params_original['entity']->getEstadoImagen() !== 4){
                        $ot = new OrdenTrabajo();
                        $ot->setPais($this->getSessionCountry());
                        $ot->setTipo(1);
                        $ot->setEstadoOrden(0);
                        $ot->setCodigoUser($this->getSessionUser()->getCodigo());
                        $ot->setFechaLimite(new \DateTime(date('Y-m-d')));
                        $em->persist($ot);
                        $em->flush($ot);
                    }
                }

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
     * @RequiresPermission(submodule="work_order_{type}", permissions="U")
     * @Method("POST")
     */
    public function createAction(Request $request, $id, $type)
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

        $form = $this->createNamedForm('work_order_img_popup', 'AppBundle\Form\OrdenTrabajoImagenType', $entity, array('is_popup' => true));
        $params_original = array('entity' => null);

        if ($request->getMethod() == 'POST'){

            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved){
                return $this->redirect($this->generateUrl('work_order_img_edit', array('id' => $entity->getToken(), 'isPopup' => 1, 'type' => $type)));
            }

        }

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'type' => $type,
            'entity' => $entity
        ));
    }

    /**
     * @Route("/{id}/edit", name="work_order_img_edit")
     * @RequiresPermission(submodule="work_order_{type}", permissions="R")
     * @Method("GET")
     */
    public function editAction($id, $type)
    {

        $em = $this->getDoctrine()->getManager();
        $isPopup = $this->getVar('isPopup');

        $entity = $em->getRepository('VallasModelBundle:Imagen')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createNamedForm('work_order_img_popup', 'AppBundle\Form\OrdenTrabajoImagenType', $entity, array('is_popup' => true, 'editable' => $this->checkActionPermissions('work_order_{type}', 'U'))) :
                                    $this->createForm('AppBundle\Form\OrdenTrabajoImagenType', $entity, array('editable' => $this->checkActionPermissions('work_order_{type}', 'U')));

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $form->createView(),
            'isPopup' => $isPopup
        ));
    }

    /**
     * @Route("/{id}/update", name="work_order_img_update")
     * @RequiresPermission(submodule="work_order_{type}", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id, $type)
    {
        $origin = $this->getVar('origin');
        $isValidation = $this->getVar('validation') == '1';
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Imagen')->getOneByToken($id);
        $isPopup = $this->getVar('isPopup');

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createNamedForm('work_order_img_popup', 'AppBundle\Form\OrdenTrabajoImagenType', $entity, array('is_popup' => true)) :
                                    $this->createForm('AppBundle\Form\OrdenTrabajoImagenType', $entity);

        $params_original = array('entity' => clone $entity);

        if ($request->getMethod() == 'POST'){
            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved && $origin != 'list'){

                return $this->redirect($this->generateUrl('work_order_img_edit', array('id' => $entity->getToken(), 'isPopup' => $isPopup, 'type' => $type,)));
            }
        }

        if ($origin == 'list'){
            return $this->render('AppBundle:screens/work_order_img:form_list.html.twig', array(
                'form' => $this->createForm('AppBundle\Form\OrdenTrabajoImagenType', $entity)->createView(),
                'entity' => $entity,
                'type' => $type,
                'imgValidation' => $isValidation
            ));
        }

        return $this->render('AppBundle:screens/work_order_img:form.html.twig', array(
            'form' => $form->createView(),
            'type' => $type,
            'entity' => $entity
        ));
    }
}