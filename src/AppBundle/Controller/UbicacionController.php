<?php

namespace AppBundle\Controller;

use AppBundle\Form\UbicacionImagenType;
use AppBundle\Form\UbicacionType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\Ubicacion;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Class UbicacionController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Ubicacion controller.
 *
 * @Route("/{_locale}/ubicaciones", defaults={"_locale"="en"})
 */
class UbicacionController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Ubicacion');
        $qb = $repository->getAllQueryBuilder();

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'ubicacion', 'categoria', 'trafico_vehicular', 'trafico_transeuntes', 'nivel_socioeconomico', 'estatus'));
        $jsonList->setSearchFields(array('ubicacion', 'categoria', 'trafico_vehicular', 'trafico_transeuntes', 'nivel_socioeconomico', 'estatus'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Ubicacion entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="ubicacion_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {

        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="ubicacion_list")
     * @RequiresPermission(submodule="ubicacion", permissions="R")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/ubicacion:index.html.twig', array(

        ));
    }

    /**
     * @Route("/add", name="ubicacion_add")
     * @RequiresPermission(submodule="ubicacion", permissions="C")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Ubicacion();
        $entity->setPais($this->getSessionCountry());

        return $this->render('AppBundle:screens/ubicacion:form.html.twig', array(
            'isNew' => true,
            'entity' => $entity,
            'form' => $this->createForm(new UbicacionType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/create", name="ubicacion_create")
     * @RequiresPermission(submodule="ubicacion", permissions="C")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Ubicacion();
        $entity->setPais($this->getSessionCountry());

        $params_original = array('entity' => null);

        $form = $this->createForm(new UbicacionType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('ubicacion_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/ubicacion:form.html.twig', array(
            'isNew' => true,
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="ubicacion_edit", options={"expose"=true})
     * @RequiresPermission(submodule="ubicacion", permissions="R")
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Ubicacion')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Ubicacion entity.');
        }

        $qbImage = $em->getRepository('VallasModelBundle:ImagenUbicacion')->getAllQueryBuilder()->andWhere('p.ubicacion = :ot')->setParameter('ot', $entity->getPkUbicacion());
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('ubicacion_img_list');
        $imgPaged->setParam('id', $entity->getToken());

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/ubicacion:form.html.twig', array(
            'paramForm' => $this->getVar('pForm'),
            'isNew' => false,
            'entity' => $entity,
            'form' => $this->createForm(new UbicacionType(), $entity, array('editable' => $this->checkActionPermissions('ubicacion', 'U')))->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new UbicacionImagenType(), $firstImg, array('editable' => $this->checkActionPermissions('ubicacion', 'U')))->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

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
     * @Route("/{id}/update", name="ubicacion_update")
     * @RequiresPermission(submodule="ubicacion", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $paramForm = $this->getVar('pForm');
        $entity = $em->getRepository('VallasModelBundle:Ubicacion')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Ubicacion entity.');
        }

        $form = $this->createForm(new UbicacionType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('ubicacion_edit', array('id' => $entity->getToken(), 'pForm' => $paramForm)));
        }

        $qbImage = $em->getRepository('VallasModelBundle:ImagenUbicacion')->getAllQueryBuilder()->andWhere('p.ubicacion = :ot')->setParameter('ot', $entity->getPkUbicacion());
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('ubicacion_img_list');
        $imgPaged->setParam('id', $entity->getToken());

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/ubicacion:form.html.twig', array(
            'paramForm' => $paramForm,
            'isNew' => false,
            'entity' => $entity,
            'form' => $form->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new UbicacionImagenType(), $firstImg)->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="ubicacion_delete", options={"expose"=true})
     * @RequiresPermission(submodule="ubicacion", permissions="D")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Ubicacion')->getOneByToken($id);
        if ($entity){
            $entity->setEstado(false);
            $em->persist($entity);
            $em->flush($entity);

            return new JsonResponse(array('result' => '1', 'message' => $this->get('translator')->trans('form.notice.deleted_success')));

        }else{

            return new JsonResponse(array('result' => '0', 'message' => $this->get('translator')->trans('form.notice.deleted_error')));
        }

    }

    /**
     * @Route("/select", name="ubicacion_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/ubicacion:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }


}