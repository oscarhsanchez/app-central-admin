<?php

namespace AppBundle\Controller;

use AppBundle\Form\MedioType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Vallas\ModelBundle\Entity\Medio;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Class MedioController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Medio controller.
 *
 * @Route("/{_locale}/medios", defaults={"_locale"="es"})
 */
class MedioController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $ubicacion = $this->getVar('ubicacion');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Medio');
        $qb = $repository->getAllQueryBuilder()->leftJoin('p.ubicacion', 'ubi')->leftJoin('p.subtipoMedio', 'subtipoMedio')->addOrderBy('ubi.ubicacion', 'ASC');

        if ($ubicacion){
            $qb->andWhere('p.ubicacion = :ubicacion')->setParameter('ubicacion', $ubicacion);
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('pk_medio', 'id_cara', 'token', 'posicion', 'ubicacion__ubicacion', 'subtipoMedio__descripcion', 'tipo_medio', 'ubicacion__latitud', 'ubicacion__longitud', 'estado'));
        $jsonList->setSearchFields(array('pk_medio', 'posicion', 'ubicacion__ubicacion', 'subtipoMedio__descripcion', 'tipo_medio', 'estado'));
        $jsonList->setOrderFields(array('', 'pk_medio', 'ubi__ubicacion', 'posicion', 'id_cara', 'tipo_medio', 'subtipoMedio__descripcion', 'estado'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Medio entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="medio_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $response = $this->getDatatableManager()->getResults();

        foreach($response['aaData'] as $key=>$row) {
            $reg = $response['aaData'][$key];

            $toString = '';
            if ($reg['ubicacion__ubicacion']){ $toString .= $reg['ubicacion__ubicacion'].' '; }
            if ($reg['tipo_medio']){ $toString .= $reg['tipo_medio'].' '; }
            if ($reg['subtipoMedio__descripcion']){ $toString .= $reg['subtipoMedio__descripcion'].' '; }

            $response['aaData'][$key]['name'] = $toString;
        }

        return new JsonResponse($response);

    }

    /**
     * @Route("/select", name="medio_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/medio:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }

    /**
     * @Route("/add", name="medio_add")
     * @RequiresPermission(submodule="medio", permissions="C")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $ubicacion_id = $this->getVar('ubicacion');
        $ubicacion = $ubicacion_id ? $em->getRepository('VallasModelBundle:Ubicacion')->find($ubicacion_id) : null;

        if ($ubicacion_id && !$ubicacion){
            throw $this->createNotFoundException('Unable to find Ubicacion entity.');
        }

        $entity = new Medio();
        $entity->setFkPais($this->getSessionCountry());
        if ($ubicacion){
            $entity->setUbicacion($ubicacion);
        }

        return $this->render('AppBundle:screens/medio:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\MedioType', $entity)->createView()
        ));
    }

    /**
     * @Route("/create", name="medio_create")
     * @RequiresPermission(submodule="medio", permissions="C")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $ubicacion_id = $this->getVar('ubicacion');
        $ubicacion = $ubicacion_id ? $em->getRepository('VallasModelBundle:Ubicacion')->find($ubicacion_id) : null;

        if ($ubicacion_id && !$ubicacion){
            throw $this->createNotFoundException('Unable to find Ubicacion entity.');
        }

        $entity = new Medio();
        $entity->setFkPais($this->getSessionCountry());
        if ($ubicacion){
            $entity->setUbicacion($ubicacion);
        }

        $params_original = array('entity' => null);

        $form = $this->createForm('AppBundle\Form\MedioType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('medio_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/medio:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="medio_edit", options={"expose"=true})
     * @RequiresPermission(submodule="medio", permissions="R")
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Medio')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Medio entity.');
        }

        return $this->render('AppBundle:screens/medio:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\MedioType', $entity, array('editable' => $this->checkActionPermissions('medio', 'U')))->createView(),
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
     * @Route("/{id}/update", name="medio_update")
     * @RequiresPermission(submodule="medio", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Medio')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Medio entity.');
        }

        $form = $this->createForm('AppBundle\Form\MedioType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('medio_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/medio:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/delete", name="medio_delete", options={"expose"=true})
     * @RequiresPermission(submodule="medio", permissions="D")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Medio')->getOneByToken($id);
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
     * @Route("/{id}/availability", name="medio_availability", options={"expose"=true})
     *
     * @Method("GET")
     */
    public function availabilityAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Medio')->getOneByToken($id, array('ubicacion' => null));

        return $this->render('AppBundle:screens/ubicacion/disponibilidad/calendar:medio.html.twig', array('medio' => $entity));

    }
}