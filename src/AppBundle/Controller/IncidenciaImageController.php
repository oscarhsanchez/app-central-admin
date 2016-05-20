<?php

namespace AppBundle\Controller;

/**
 * Class IncidenciaImageController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
use AppBundle\Form\IncidenciaImagenType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Vallas\ModelBundle\Entity\Imagen;
use Vallas\ModelBundle\Entity\ImagenIncidencia;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Imagen controller.
 *
 * @Route("/{_locale}/incidencia-images/{type}", defaults={"_locale"="es"})
 */
class IncidenciaImageController extends VallasAdminController {

    /**
     * @Route("/", name="incidencia_img_list")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="R")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $token = $this->getVar('id');

        $incidencia = null;
        $qbImage = $em->getRepository('VallasModelBundle:ImagenIncidencia')->getAllQueryBuilder()->leftJoin('p.incidencia', 'incidencia');
        $qbImage->addOrderBy('p.created_at', 'DESC');

        if ($token){
            $incidencia = $token ? $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($token) : null;
            if ($incidencia){
                $qbImage->andWhere('p.incidencia = :ot')->setParameter('ot', $incidencia->getPkIncidencia());
            }
        }

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, $request->query->getInt('page', 1), 1);
        $imgPaged->setUsedRoute('incidencia_img_list');
        if ($token) $imgPaged->setParam('id', $token);

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/incidencia_img:list.html.twig', array(
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formImage' => $this->createForm('AppBundle\Form\IncidenciaImagenType', $firstImg)->createView(),
            'entity' => $incidencia,
            'type' => $type,
        ));
    }

    /**
     * @Route("/{id}/add", name="incidencia_img_add")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="U")
     * @Method("GET")
     */
    public function addAction($id, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find Incidencia entity.');
        }

        $entity = new ImagenIncidencia();
        $entity->setIncidencia($ot);
        $entity->setPais($this->getSessionCountry());
        $form = $this->createForm(new IncidenciaImagenType(array('_form_name' => 'incidencia_img_popup')), $entity, array('is_popup' => true));

        return $this->render('AppBundle:screens/incidencia_img:form.html.twig', array(
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

                $entity->setPath($imagenUpload);
                $entity->setUrl('/media/incidencia_imagen');

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
     * @Route("/{id}/create", name="incidencia_img_create")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="U")
     * @Method("POST")
     */
    public function createAction(Request $request, $id, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find Incidencia entity.');
        }

        $entity = new ImagenIncidencia();
        $entity->setPais($this->getSessionCountry());
        $entity->setIncidencia($ot);

        $form = $this->createForm(new IncidenciaImagenType(array('_form_name' => 'incidencia_img_popup')), $entity, array('is_popup' => true));
        $params_original = array('entity' => null);

        if ($request->getMethod() == 'POST'){

            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved){
                return $this->redirect($this->generateUrl('incidencia_img_edit', array('id' => $entity->getToken(), 'type' => $type, 'isPopup' => 1)));
            }

        }

        return $this->render('AppBundle:screens/incidencia_img:form.html.twig', array(
            'form' => $form->createView(),
            'type' => $type,
            'entity' => $entity
        ));
    }

    /**
     * @Route("/{id}/edit", name="incidencia_img_edit")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="R")
     * @Method("GET")
     */
    public function editAction($id, $type)
    {

        $em = $this->getDoctrine()->getManager();
        $isPopup = $this->getVar('isPopup');

        $entity = $em->getRepository('VallasModelBundle:ImagenIncidencia')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createForm(new IncidenciaImagenType(array('_form_name' => 'incidencia_img_popup')), $entity, array('is_popup' => true, 'editable' => $this->checkActionPermissions('incidencia_{type}', 'U'))) :
                                    $this->createForm('AppBundle\Form\IncidenciaImagenType', $entity, array('editable' => $this->checkActionPermissions('incidencia_{type}', 'U')));

        return $this->render('AppBundle:screens/incidencia_img:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $form->createView(),
            'isPopup' => $isPopup
        ));
    }

    /**
     * @Route("/{id}/update", name="incidencia_img_update")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id, $type)
    {
        $origin = $this->getVar('origin');
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:ImagenIncidencia')->getOneByToken($id);
        $isPopup = $this->getVar('isPopup');

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createForm(new IncidenciaImagenType(array('_form_name' => 'incidencia_img_popup')), $entity, array('is_popup' => true)) :
                                    $this->createForm('AppBundle\Form\IncidenciaImagenType', $entity);

        $params_original = array('entity' => clone $entity);

        if ($request->getMethod() == 'POST'){
            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved && $origin != 'list'){

                return $this->redirect($this->generateUrl('incidencia_img_edit', array('id' => $entity->getToken(), 'type' => $type, 'isPopup' => $isPopup)));
            }
        }

        if ($origin == 'list'){
            return $this->render('AppBundle:screens/incidencia_img:form_list.html.twig', array(
                'form' => $this->createForm('AppBundle\Form\IncidenciaImagenType', $entity)->createView(),
                'type' => $type,
                'entity' => $entity
            ));
        }

        return $this->render('AppBundle:screens/incidencia_img:form.html.twig', array(
            'form' => $form->createView(),
            'type' => $type,
            'entity' => $entity
        ));
    }
}