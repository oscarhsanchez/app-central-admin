<?php

namespace AppBundle\Controller;

use AppBundle\Form\MotivoOrdenesPendientesType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Vallas\ModelBundle\Entity\MotivoOrdenesPendientes;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Class MotivoOrdenesPendientesController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * MotivoOrdenesPendientes controller.
 *
 * @Route("/{_locale}/motivos-ordenes-pendientes", defaults={"_locale"="es"})
 */
class MotivoOrdenesPendientesController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:MotivoOrdenesPendientes');
        $qb = $repository->getAllQueryBuilder()->andWhere('p.estado = 1');

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'descripcion', 'tipo_incidencia'));
        $jsonList->setSearchFields(array('descripcion'));
        $jsonList->setOrderFields(array('','descripcion', 'tipo_incidencia'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of MotivoOrdenesPendientes entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="motivo_ordenes_pendientes_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $response = $this->getDatatableManager()->getResults();

        foreach($response['aaData'] as $key=>$row) {
            $reg = $response['aaData'][$key];
            $response['aaData'][$key]['tipo_incidencia'] = $this->getTypeUrlByCode($reg['tipo_incidencia']);
        }

        return new JsonResponse($response);

    }

    private function getTypeUrlByCode($code){
        switch($code){
            case '0': return 'fixing';
            case '1': return 'monitoring';
            case '2': return 'installation';
            case '3': return 'lighting';
            case '4': return 'plane';
            case '5': return 'others';
        }
        return '';
    }

    /**
     * @Route("/", name="motivo_ordenes_pendientes_list")
     * @RequiresPermission(submodule="motivo_ordenes_pendientes", permissions="R")
     * @Method("GET")
     */
    public function indexAction()
    {
        return $this->render('AppBundle:screens/motivo_ordenes_pendientes:index.html.twig', array(

        ));
    }

    /**
     * @Route("/select", name="motivo_ordenes_pendientes_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/motivo_ordenes_pendientes:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }

    /**
     * @Route("/add", name="motivo_ordenes_pendientes_add")
     * @RequiresPermission(submodule="motivo_ordenes_pendientes", permissions="C")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new MotivoOrdenesPendientes();
        $entity->setPais($this->getSessionCountry());

        return $this->render('AppBundle:screens/motivo_ordenes_pendientes:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new MotivoOrdenesPendientesType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/create", name="motivo_ordenes_pendientes_create")
     * @RequiresPermission(submodule="motivo_ordenes_pendientes", permissions="C")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new MotivoOrdenesPendientes();
        $entity->setPais($this->getSessionCountry());

        $params_original = array('entity' => null);

        $form = $this->createForm(new MotivoOrdenesPendientesType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('motivo_ordenes_pendientes_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/motivo_ordenes_pendientes:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="motivo_ordenes_pendientes_edit", options={"expose"=true})
     * @RequiresPermission(submodule="motivo_ordenes_pendientes", permissions="R")
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:MotivoOrdenesPendientes')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find MotivoOrdenesPendientes entity.');
        }

        return $this->render('AppBundle:screens/motivo_ordenes_pendientes:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new MotivoOrdenesPendientesType(), $entity, array('editable' => $this->checkActionPermissions('motivo_ordenes_pendientes', 'U')))->createView(),
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
     * @Route("/{id}/update", name="motivo_ordenes_pendientes_update")
     * @RequiresPermission(submodule="motivo_ordenes_pendientes", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:MotivoOrdenesPendientes')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find MotivoOrdenesPendientes entity.');
        }

        $form = $this->createForm(new MotivoOrdenesPendientesType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('motivo_ordenes_pendientes_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/motivo_ordenes_pendientes:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/delete", name="motivo_ordenes_pendientes_delete", options={"expose"=true})
     * @RequiresPermission(submodule="motivo_ordenes_pendientes", permissions="D")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:MotivoOrdenesPendientes')->getOneByToken($id);
        if ($entity){
            $entity->setEstado(false);
            $em->persist($entity);
            $em->flush($entity);

            return new JsonResponse(array('result' => '1', 'message' => $this->get('translator')->trans('form.notice.deleted_success')));

        }else{

            return new JsonResponse(array('result' => '0', 'message' => $this->get('translator')->trans('form.notice.deleted_error')));
        }

    }

}