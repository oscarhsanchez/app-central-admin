<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Util\Database;
use Vallas\ModelBundle\Entity\AgrupacionMedioDetalle;
use Vallas\ModelBundle\Entity\ReportCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;


/**
 * AgrupacionMedioDetalle controller.

 * @Route("/{_locale}/location-group-detail")
 * @Route("/location-group-detail", defaults={"_locale"="es"})
 */
class AgrupacionMedioDetalleController extends VallasAdminController
{

    /**
     * Returns a list of AgrupacionMedioDetalle entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="agrupacion_medio_detalle_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:AgrupacionMedioDetalle');
        $qb = $repository->getQueryBuilder()->leftJoin('p.medio', 'medio')->leftJoin('medio.ubicacion', 'ubicacion')->andWhere('p.estado > 0');
        $qb->andWhere('p.agrupacion_medio = :agrId')->setParameter('agrId', $this->getVar('agrupacion_medio_id'));

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'pk_agrupacion_detalle', 'factor_agrupacion', 'medio__pk_medio', 'medio__id_cara', 'medio__posicion', 'medio__ubicacion__ubicacion'));
        $jsonList->setSearchFields(array('factor_agrupacion', 'medio__pk_medio', 'medio__id_cara', 'medio__posicion', 'medio__ubicacion__ubicacion'));
        $jsonList->setOrderFields(array('','medio__pk_medio', 'medio__ubicacion__ubicacion', 'medio__posicion', 'medio__id_cara', 'factor_agrupacion'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);
        $response = $jsonList->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/{id}/edit", name="agrupacion_medio_detalle_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:AgrupacionMedioDetalle')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find AgrupacionMedioDetalle entity.');
        }

        return $this->render('AppBundle:screens/agrupacion_medio_detalle:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\AgrupacionMedioDetalleType', $entity)->createView()
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
     * @Route("/{id}/update", name="agrupacion_medio_detalle_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:AgrupacionMedioDetalle')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find AgrupacionMedioDetalle entity.');
        }

        $form = $this->createForm('AppBundle\Form\AgrupacionMedioDetalleType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('agrupacion_medio_detalle_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/agrupacion_medio_detalle:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="agrupacion_medio_detalle_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:AgrupacionMedioDetalle')->getOneByToken($id);

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
     * @Route("/{agrupacion_medio_id}/create", name="agrupacion_medio_detalle_create")
     * @Method("POST")
     */
    public function createAction(Request $request, $agrupacion_medio_id)
    {

        $em = $this->getDoctrine()->getManager();
        $agrupacionMedio = $em->getRepository('VallasModelBundle:AgrupacionMedio')->getOneByToken($agrupacion_medio_id);
        if (!$agrupacionMedio){
            throw $this->createNotFoundException('Unable to find AgrupacionMedio entity.');
        }

        $entity = new AgrupacionMedioDetalle();
        $entity->setAgrupacionMedio($agrupacionMedio);
        $entity->setPais($this->getSessionCountry());

        $params_original = array();

        $form = $this->createForm('AppBundle\Form\AgrupacionMedioDetalleType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('agrupacion_medio_detalle_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/agrupacion_medio_detalle:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{agrupacion_medio_id}/add", name="agrupacion_medio_detalle_add")
     * @Method("GET")
     */
    public function addAction($agrupacion_medio_id)
    {
        $em = $this->getDoctrine()->getManager();
        $agrupacionMedio = $em->getRepository('VallasModelBundle:AgrupacionMedio')->getOneByToken($agrupacion_medio_id);
        if (!$agrupacionMedio){
            throw $this->createNotFoundException('Unable to find AgrupacionMedio entity.');
        }

        $entity = new AgrupacionMedioDetalle();
        $entity->setAgrupacionMedio($agrupacionMedio);
        $entity->setPais($this->getSessionCountry());

        return $this->render('AppBundle:screens/agrupacion_medio_detalle:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\AgrupacionMedioDetalleType', $entity)->createView()
        ));
    }

}