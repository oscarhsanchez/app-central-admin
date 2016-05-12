<?php

namespace AppBundle\Controller;

use AppBundle\Form\ZonaType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\Zona;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Class ZoneController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Zona controller.
 *
 * @Route("/{_locale}/zones", defaults={"_locale"="es"})
 */
class ZoneController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager($type=null)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Zona');
        $qb = $repository->getAllQueryBuilder()->andWhere('p.estado = 1');

        if ($type){
            $qb->andWhere('p.tipo = :tipo')->setParameter('tipo', $this->getCodeTypeByUrlType($type));
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'nombre'));
        $jsonList->setSearchFields(array('nombre'));
        $jsonList->setOrderFields(array('','nombre'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Zona entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/{_type}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="zone_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request, $_type)
    {
        $request = $this->get('request_stack')->getCurrentRequest();

        $response = $this->getDatatableManager($_type)->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/{type}", name="zone_list")
     * @RequiresPermission(submodule="zone_{type}", permissions="R")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/zone:index.html.twig', array(
            'type' => $type,
        ));
    }

    /**
     * @Route("/{type}/add", name="zone_add")
     * @RequiresPermission(submodule="zone_{type}", permissions="C")
     * @Method("GET")
     */
    public function addAction($type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Zona();
        $entity->setPais($this->getSessionCountry());
        $entity->setTipo($this->getCodeTypeByUrlType($type));

        return $this->render('AppBundle:screens/zone:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $this->createForm(new ZonaType(), $entity)->createView()
        ));
    }

    private function getTypeUrlByCode($code){
        switch($code){
            case '0': return 'fixing';
            case '1': return 'monitoring';
            case '2': return 'installation';
            case '3': return 'lighting';
        }
        return '';
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
     * @Route("/{type}/create", name="zone_create")
     * @RequiresPermission(submodule="zone_{type}", permissions="C")
     * @Method("POST")
     */
    public function createAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Zona();
        $entity->setPais($this->getSessionCountry());
        $entity->setTipo($this->getCodeTypeByUrlType($type));
        $params_original = array('entity' => null);

        $form = $this->createForm(new ZonaType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('zone_edit', array('id' => $entity->getToken(), 'type' => $type)));
        }

        return $this->render('AppBundle:screens/zone:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{type}/{id}/edit", name="zone_edit", options={"expose"=true})
     * @RequiresPermission(submodule="zone_{type}", permissions="R")
     * @Method("GET")
     */
    public function editAction($id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Zona')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Zona entity.');
        }

        return $this->render('AppBundle:screens/zone:form.html.twig', array(
            'entity' => $entity,
            'type' => $this->getTypeUrlByCode($entity->getTipo()),
            'form' => $this->createForm(new ZonaType(), $entity, array('editable' => $this->checkActionPermissions('zone_{type}', 'U')))->createView(),
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
     * @Route("/{type}/{id}/update", name="zone_update")
     * @RequiresPermission(submodule="zone_{type}", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Zona')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Zona entity.');
        }

        $form = $this->createForm(new ZonaType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('zone_edit', array('id' => $entity->getToken(), 'type' => $type)));
        }

        return $this->render('AppBundle:screens/zone:form.html.twig', array(
            'entity' => $entity,
            'type' => $this->getTypeUrlByCode($entity->getTipo()),
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{type}/{id}/delete", name="zone_delete", options={"expose"=true})
     * @RequiresPermission(submodule="zone_{type}", permissions="D")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Zona')->getOneByToken($id);
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
     * @Route("/{_type}/select", name="zone_select")
     * @Method("GET")
     */
    public function selectAction($_type)
    {
        return $this->render('AppBundle:screens/zone:select.html.twig', array(
            'getVars' => $this->getVar(),
            'type' => $_type
        ));
    }
}