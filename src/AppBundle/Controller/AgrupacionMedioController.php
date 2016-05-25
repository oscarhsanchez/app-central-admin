<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Util\Database;
use Vallas\ModelBundle\Entity\AgrupacionMedio;
use Vallas\ModelBundle\Entity\ReportCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;


/**
 * AgrupacionMedio controller.

 * @Route("/{_locale}/location-group")
 * @Route("/location-group", defaults={"_locale"="es"})
 */
class AgrupacionMedioController extends VallasAdminController
{

    /**
     * Returns a list of AgrupacionMedio entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="agrupacion_medio_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:AgrupacionMedio');

        $qb = $repository->getQueryBuilder();

        if ($ubicacion_id = $this->getVar('ubicacion')) {
            $qb->andWhere('p.ubicacion = :ubi')->setParameter('ubi', $ubicacion_id);
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'pk_agrupacion', 'descripcion'));
        $jsonList->setSearchFields(array('descripcion'));
        $jsonList->setOrderFields(array('','descripcion'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb->andWhere('p.estado > 0'));
        $response = $jsonList->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="agrupacion_medio_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:screens/agrupacion_medio:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="agrupacion_medio_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:AgrupacionMedio')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find AgrupacionMedio entity.');
        }

        return $this->render('AppBundle:screens/agrupacion_medio:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\AgrupacionMedioType', $entity)->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                Database::saveEntity($em, $entity);
                $this->get('session')->getFlashBag()->add('notice', 'form.notice.saved_success');

                return true;

            }else{
                $this->get('session')->getFlashBag()->add('error', 'form.notice.saved_error');
                return false;
            }
        }

    }

    /**
     * @Route("/{id}/update", name="agrupacion_medio_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:AgrupacionMedio')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find AgrupacionMedio entity.');
        }

        $form = $this->createForm('AppBundle\Form\AgrupacionMedioType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('agrupacion_medio_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/agrupacion_medio:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="agrupacion_medio_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:AgrupacionMedio')->getOneByToken($id);

        if ($entity){
            $entity->setEstado(false);
            $em->persist($entity);
            $em->flush();

            return new JsonResponse(array('result' => '1', 'message' => $this->get('translator')->trans('form.notice.deleted_success')));

        }else{

            return new JsonResponse(array('result' => '0', 'message' => $this->get('translator')->trans('form.notice.deleted_error')));
        }

    }

    /**
     * @Route("/create", name="agrupacion_medio_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {

        $entity = new AgrupacionMedio();
        $entity->setPais($this->getSessionCountry());

        $ubicacion = null;
        if ($ubicacionID = $this->getVar('ubicacion')){
            $em = $this->getDoctrine()->getManager();
            $ubicacion = $em->getRepository('VallasModelBundle:Ubicacion')->find($ubicacionID);

            if (!$ubicacion){
                throw $this->createNotFoundException('Unable to find Ubicacion entity.');
            }

            $entity->setUbicacion($ubicacion);
        }

        $params_original = array();

        $form = $this->createForm('AppBundle\Form\AgrupacionMedioType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('agrupacion_medio_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/agrupacion_medio:form.html.twig', array(
            'entity' => $entity,
            'ubicacion' => $ubicacion,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="agrupacion_medio_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $entity = new AgrupacionMedio();
        $entity->setPais($this->getSessionCountry());
        $ubicacion = null;

        if ($ubicacionID = $this->getVar('ubicacion')){
            $em = $this->getDoctrine()->getManager();
            $ubicacion = $em->getRepository('VallasModelBundle:Ubicacion')->find($ubicacionID);

            if (!$ubicacion){
                throw $this->createNotFoundException('Unable to find Ubicacion entity.');
            }

            $entity->setUbicacion($ubicacion);
        }

        return $this->render('AppBundle:screens/agrupacion_medio:form.html.twig', array(
            'entity' => $entity,
            'ubicacion' => $ubicacion,
            'form' => $this->createForm('AppBundle\Form\AgrupacionMedioType', $entity)->createView()
        ));
    }

}