<?php

namespace AppBundle\Controller;

/**
 * Class UbicacionImageController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
use AppBundle\Form\UbicacionImagenType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Vallas\ModelBundle\Entity\Imagen;
use Vallas\ModelBundle\Entity\ImagenUbicacion;

/**
 * Imagen controller.
 *
 * @Route("/{_locale}/ubicacion-images", defaults={"_locale"="en"})
 */
class UbicacionImageController extends VallasAdminController {

    /**
     * @Route("/", name="ubicacion_img_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $token = $this->getVar('id');

        $ubicacion = null;
        $qbImage = $em->getRepository('VallasModelBundle:ImagenUbicacion')->getAllQueryBuilder()->leftJoin('p.ubicacion', 'ubicacion');
        $qbImage->addOrderBy('p.created_at', 'DESC');

        if ($token){
            $ubicacion = $token ? $em->getRepository('VallasModelBundle:Ubicacion')->getOneByToken($token) : null;
            if ($ubicacion){
                $qbImage->andWhere('p.ubicacion = :ot')->setParameter('ot', $ubicacion->getPkUbicacion());
            }
        }

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, $request->query->getInt('page', 1), 1);
        $imgPaged->setUsedRoute('ubicacion_img_list');
        if ($token) $imgPaged->setParam('id', $token);

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/ubicacion_img:list.html.twig', array(
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formImage' => $this->createForm(new UbicacionImagenType(), $firstImg)->createView(),
            'entity' => $ubicacion
        ));
    }

    /**
     * @Route("/{id}/add", name="ubicacion_img_add")
     * @Method("GET")
     */
    public function addAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:Ubicacion')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find Ubicacion entity.');
        }

        $entity = new ImagenUbicacion();
        $entity->setUbicacion($ot);
        $entity->setPais($this->getSessionCountry());
        $form = $this->createForm(new UbicacionImagenType(array('_form_name' => 'ubicacion_img_popup')), $entity);

        return $this->render('AppBundle:screens/ubicacion_img:form.html.twig', array(
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
                $imagenUpload = $uploadable_manager->processUploadedFile($form->get('nombre'), $post['nombre'], array_key_exists('entity', $params_original) && $params_original['entity'] ? $params_original['entity']->getNombre() : null);

                $entity->setPath($imagenUpload);
                $entity->setUrl('/media/ubicacion_imagen');

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
     * @Route("/{id}/create", name="ubicacion_img_create")
     * @Method("POST")
     */
    public function createAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $em = $this->getDoctrine()->getManager();
        $ot = $em->getRepository('VallasModelBundle:Ubicacion')->getOneByToken($id);

        if (!$ot){
            throw $this->createNotFoundException('Unable to find Ubicacion entity.');
        }

        $entity = new ImagenUbicacion();
        $entity->setPais($this->getSessionCountry());
        $entity->setUbicacion($ot);

        $form = $this->createForm(new UbicacionImagenType(array('_form_name' => 'ubicacion_img_popup')), $entity);
        $params_original = array('entity' => null);

        if ($request->getMethod() == 'POST'){

            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved){
                return $this->redirect($this->generateUrl('ubicacion_img_edit', array('id' => $entity->getToken(), 'isPopup' => 1)));
            }

        }

        return $this->render('AppBundle:screens/ubicacion_img:form.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }

    /**
     * @Route("/{id}/edit", name="ubicacion_img_edit")
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $isPopup = $this->getVar('isPopup');

        $entity = $em->getRepository('VallasModelBundle:ImagenUbicacion')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createForm(new UbicacionImagenType(array('_form_name' => 'ubicacion_img_popup')), $entity) :
                                    $this->createForm(new UbicacionImagenType(), $entity);

        return $this->render('AppBundle:screens/ubicacion_img:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
            'isPopup' => $isPopup
        ));
    }

    /**
     * @Route("/{id}/update", name="ubicacion_img_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {
        $origin = $this->getVar('origin');
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:ImagenUbicacion')->getOneByToken($id);
        $isPopup = $this->getVar('isPopup');

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Imagen entity.');
        }

        $form = $isPopup == '1' ? $this->createForm(new UbicacionImagenType(array('_form_name' => 'ubicacion_img_popup')), $entity) :
                                    $this->createForm(new UbicacionImagenType(), $entity);

        $params_original = array('entity' => clone $entity);

        if ($request->getMethod() == 'POST'){
            $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

            if ($boolSaved && $origin != 'list'){

                return $this->redirect($this->generateUrl('ubicacion_img_edit', array('id' => $entity->getToken(), 'isPopup' => $isPopup)));
            }
        }

        if ($origin == 'list'){
            return $this->render('AppBundle:screens/ubicacion_img:form_list.html.twig', array(
                'form' => $this->createForm(new UbicacionImagenType(), $entity)->createView(),
                'entity' => $entity
            ));
        }

        return $this->render('AppBundle:screens/ubicacion_img:form.html.twig', array(
            'form' => $form->createView(),
            'entity' => $entity
        ));
    }
}