<?php

namespace AppBundle\Controller;

use AppBundle\Form\OrdenTrabajoFieldType;
use AppBundle\Form\OrdenTrabajoImagenType;
use AppBundle\Form\OrdenTrabajoType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\LogOrdenTrabajo;
use Vallas\ModelBundle\Entity\OrdenTrabajo;

/**
 * Class WorkOrderController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * OrdenTrabajo controller.
 *
 * @Route("/{_locale}/work-orders", defaults={"_locale"="en"})
 */
class WorkOrderController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager($type=null)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:OrdenTrabajo');
        $qb = $repository->getAllQueryBuilder();

        if ($type){
            $qb->andWhere('p.tipo = :tipo')->setParameter('tipo', $this->getCodeTypeByUrlType($type));
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'pk_orden_trabajo', 'estado_orden', 'created_at', 'fecha_cierre', 'fecha_limite', 'medio__ubicacion__ubicacion', 'codigo_user'));
        $jsonList->setSearchFields(array('fecha_limite', 'fecha_cierre', 'medio__ubicacion__ubicacion', 'codigo_user'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of OrdenTrabajo entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/{_type}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_all" = "all" }, name="work_order_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request, $_type)
    {
        $request = $this->get('request_stack')->getCurrentRequest();

        $response = $this->getDatatableManager($_type)->getResults();

        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

            $now = new \DateTime('now');
            $tomorrow = new \DateTime('now');
            $tomorrow->add(new \DateInterval('P2D'));

            $priority = 2;
            if ($reg['fecha_limite'] > $now){
                $priority = 0;
                $diff = $reg['fecha_limite']->diff($now);

                if ($diff->days < 5 || ($diff->days == 5 && $diff->h == 0 && $diff->i == 0 && $diff->s == 0)) $priority = 1;
                if ($diff->days < 2 || ($diff->days == 2 && $diff->h == 0 && $diff->i == 0 && $diff->s == 0)) $priority = 2;
            }

            if ($reg['estado_orden'] && $reg['estado_orden'] == 2){
                $priority = null;
            }

            $response['aaData'][$key]['fecha_limite'] = $reg['fecha_limite']->format('d/m/Y');
            $response['aaData'][$key]['priority'] = $priority;
            $response['aaData'][$key]['created_at'] = $reg['created_at']->format('d/m/Y');
            $response['aaData'][$key]['fecha_cierre'] = $reg['fecha_cierre'] ? $reg['fecha_cierre']->format('d/m/Y') : null;
            $response['aaData'][$key]['toString'] = $reg['fecha_limite']->format('d/m/Y') .' - '. $reg['medio__ubicacion__ubicacion'] .' - '. $reg['codigo_user'];

            $estados = array('0' => 'Pendiente', '1' => 'En proceso', '2' => 'Cerrada');
            $estado_orden = strval($reg['estado_orden']);
            $response['aaData'][$key]['estado_orden'] = $reg['estado_orden'] ? $estados[$estado_orden] : null;
        }

        return new JsonResponse($response);

    }

    /**
     * @Route("/{type}", name="work_order_list")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        //$formChangeUser = $this->createForm(new OrdenTrabajoFieldType(array('_form_name' => 'work_order_user')), null, array('type'=>'user'));
        //$formChangeDateLimit = $this->createForm(new OrdenTrabajoFieldType(array('_form_name' => 'work_order_date_limit')), null, array('type'=>'date_limit'));
        //$formChangeState = $this->createForm(new OrdenTrabajoFieldType(array('_form_name' => 'work_order_state')), null, array('type'=>'state'));

        $qbImage = $em->getRepository('VallasModelBundle:Imagen')->getAllQueryBuilder();
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('work_order_img_list');
        $imgPaged->setParam('type', $type);

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/work_order:index.html.twig', array(
            'type' => $type,
            //'formChangeUser' => $formChangeUser->createView(),
            //'formChangeDateLimit' => $formChangeDateLimit->createView(),
            //'formChangeState' => $formChangeState->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new OrdenTrabajoImagenType(), $firstImg)->createView()
        ));
    }

    /**
     * @Route("/{type}/add", name="work_order_add")
     * @Method("GET")
     */
    public function addAction($type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new OrdenTrabajo();
        $entity->setPais($this->getSessionCountry());
        $entity->setTipo($this->getCodeTypeByUrlType($type));
        //$this->initLanguagesForEntity($entity);

        return $this->render('AppBundle:screens/work_order:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $this->createForm(new OrdenTrabajoType(), $entity)->createView()
        ));
    }

    private function getTypeUrlByCode($code){
        switch($code){
            case '0': return 'fixing-monitoring';
            case '1': return 'installation';
            case '2': return 'lighting';
        }
        return '';
    }

    private function getCodeTypeByUrlType($type){
        switch($type){
            case 'fixing-monitoring': return '0';
            case 'installation': return '1';
            case 'lighting': return '2';
        }
        return '';
    }

    /**
     * @Route("/{type}/create", name="work_order_create")
     * @Method("POST")
     */
    public function createAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new OrdenTrabajo();
        $entity->setPais($this->getSessionCountry());
        $entity->setTipo($this->getCodeTypeByUrlType($type));
        //$this->initLanguagesForEntity($entity);
        $params_original = array('entity' => null);

        $form = $this->createForm(new OrdenTrabajoType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('work_order_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/work_order:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="work_order_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entityQB = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByTokenQB($id, array('logs' => null))->addOrderBy('logs.fecha', 'DESC');
        $entity = $entityQB->getQuery()->getOneOrNullResult();
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
        }

        $qbImage = $em->getRepository('VallasModelBundle:Imagen')->getAllQueryBuilder()->andWhere('p.orden_trabajo = :ot')->setParameter('ot', $entity->getPkOrdenTrabajo());
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('work_order_img_list');
        $imgPaged->setParam('type', $this->getTypeUrlByCode($entity->getTipo()));
        $imgPaged->setParam('id', $entity->getToken());

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/work_order:form.html.twig', array(
            'entity' => $entity,
            'type' => $this->getTypeUrlByCode($entity->getTipo()),
            'form' => $this->createForm(new OrdenTrabajoType(), $entity)->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new OrdenTrabajoImagenType(), $firstImg)->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                //LOG DE ORDEN DE TRABAJO
                $logAccion = 'Modificación';
                if (!$entity->getPkOrdenTrabajo()){
                    $logAccion = 'Creacion';
                }
                if (array_key_exists('entity', $params_original) && $params_original['entity']){

                    if ($params_original['entity']->getEstadoOrden() != $entity->getEstadoOrden()){
                        $logAccion = 'Cambio de estado';
                        if ($entity->getEstadoOrden() == 2){
                            $logAccion = 'Cierre';
                        }
                    }
                }
                $log = new LogOrdenTrabajo();
                $log->setPais($this->getSessionCountry());
                $log->setOrdenTrabajo($entity);
                $log->setCodigoUser($this->getSessionUser()->getCodigo());
                $log->setFecha(new \DateTime(date('Y:m:d H:i:s')));
                $log->setAccion($logAccion);

                $em->persist($entity);
                $em->persist($log);
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
     * @Route("/{type}/edit-field", name="work_order_edit_field", options={"expose"=true})
     * @Method("GET")
     */
    public function editFieldAction(Request $request, $type)
    {

        $em = $this->getDoctrine()->getManager();
        $field_type = $this->getVar('field_type');
        $id = $this->getVar('id');

        if ($id){
            $entity = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
            }
        }else{
            $entity = null;
        }

        $form = $this->createForm(new OrdenTrabajoFieldType(array('_form_name' => 'work_order_' . $field_type)), $entity, array('type' => $field_type));

        return $this->render('AppBundle:screens/work_order:form_update_field.html.twig', array('form' => $form->createView(), 'type' => $type, 'field_type' => $field_type));

    }

    /**
     * @Route("/{type}/update-field", name="work_order_update_field")
     * @Method("POST")
     */
    public function updateFieldAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $field_type = $this->getVar('field_type');

        $entityAux = new OrdenTrabajo();
        $form = $this->createForm(new OrdenTrabajoFieldType(array('_form_name' => 'work_order_'.$field_type)), $entityAux, array('type' => $field_type));

        if ($request->getMethod() == 'POST'){

            $post = $this->postVar('work_order_'.$field_type);
            $tokens = $post['tokens'] ? explode(',', $post['tokens']) : array();

            $form->handleRequest($request);

            if (count($tokens) < 1){
                $form->addError(new FormError('Debe seleccionar por lo menos un registro'));
            }

            if ($form->isValid()){

                $qb = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getQueryBuilder();
                $entities = $qb->andWhere($qb->expr()->in('p.token', $tokens))->getQuery()->getResult();

                $user = null;
                $dateLimit = null;
                $state = null;

                switch($field_type){
                    case 'user':
                        $user = $post['user'] ? $em->getRepository('VallasModelBundle:User')->find($post['user']) : null;
                        break;
                    case 'date_limit':
                        $dateLimit = $entityAux->getFechaLimite();
                        break;
                    case 'state':
                        $state = $entityAux->getEstadoOrden();
                        break;
                }

                foreach($entities as $entity){
                    switch($field_type){
                        case 'user':
                            if ($user) $entity->setCodigoUser($user->getCodigo());
                            break;
                        case 'date_limit':
                            if ($dateLimit) $entity->setFechaLimite($dateLimit);
                            break;
                        case 'state':
                            if ($state) $entity->setEstadoOrden($state);
                            break;
                    }
                    $em->persist($entity);
                }

                $em->flush();
                $this->get('session')->getFlashBag()->add('notice', $this->get('translator')->trans('form.notice.saved_success'));
            }

        }

        return $this->render('AppBundle:screens/work_order:form_update_field.html.twig', array('form' => $form->createView(), 'type' => $type, 'field_type' => $field_type));

    }

    /**
     * @Route("/{id}/update", name="work_order_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entityQB = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByTokenQB($id, array('logs' => null))->addOrderBy('logs.fecha', 'DESC');
        $entity = $entityQB->getQuery()->getOneOrNullResult();
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
        }

        $form = $this->createForm(new OrdenTrabajoType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('work_order_edit', array('id' => $entity->getToken())));
        }

        $qbImage = $em->getRepository('VallasModelBundle:Imagen')->getAllQueryBuilder()->andWhere('p.orden_trabajo = :ot')->setParameter('ot', $entity->getPkOrdenTrabajo());
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('work_order_img_list');
        $imgPaged->setParam('type', $this->getTypeUrlByCode($entity->getTipo()));
        $imgPaged->setParam('id', $entity->getToken());

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/work_order:form.html.twig', array(
            'entity' => $entity,
            'type' => $this->getTypeUrlByCode($entity->getTipo()),
            'form' => $form->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new OrdenTrabajoImagenType(), $firstImg)->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="work_order_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByToken($id);
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
     * @Route("/{id}/view-log", name="work_order_view_log", options={"expose"=true})
     * @Method("GET")
     */
    public function viewLogAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entityQB = $em->getRepository('VallasModelBundle:OrdenTrabajo')->getOneByTokenQB($id, array('logs' => null))->addOrderBy('logs.fecha', 'DESC');
        $entity = $entityQB->getQuery()->getOneOrNullResult();

        if (!$entity){
            throw $this->createNotFoundException('Unable to find OrdenTrabajo entity.');
        }

        return $this->render('AppBundle:screens/work_order:logs.html.twig', array('entity' => $entity));

    }

    /**
     * @Route("/{_type}/select", name="work_order_select")
     * @Method("GET")
     */
    public function selectAction($_type)
    {
        return $this->render('AppBundle:screens/work_order:select.html.twig', array(
            'getVars' => $this->getVar(),
            'type' => $_type
        ));
    }
}