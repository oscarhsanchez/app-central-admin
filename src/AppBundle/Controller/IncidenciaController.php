<?php
namespace AppBundle\Controller;

use AppBundle\Form\IncidenciaImagenType;
use AppBundle\Form\IncidenciaType;
use AppBundle\Form\IncidenciaFieldType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\Incidencia;
use Vallas\ModelBundle\Entity\LogIncidencia;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Class IncidenciaController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Incidencia controller.
 *
 * @Route("/{_locale}/incidencias", defaults={"_locale"="en"})
 */
class IncidenciaController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager($type=null)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Incidencia');
        $qb = $repository->getAllQueryBuilder()->andWhere('p.estado = 1');

        if ($type !== NULL){
            $qb->andWhere('p.tipo = :tipo')->setParameter('tipo', $type);
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'tipo_string', 'estado_incidencia', 'codigo_user', 'codigo_user_asignado', 'medio__ubicacion__ubicacion', 'fecha_limite', 'fecha_cierre'));
        $jsonList->setSearchFields(array('tipo', 'estado_incidencia', 'codigo_user', 'codigo_user_asignado', 'medio__ubicacion__ubicacion', 'fecha_limite', 'fecha_cierre'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Incidencia entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/{_type}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_all" = "all" }, name="incidencia_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction($_type)
    {

        $response = $this->getDatatableManager($_type)->getResults();

        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

            $medio = '';
            //if ($reg['medio__ubicacion__ubicacion']){ $medio .= $reg['medio__ubicacion__ubicacion'].' '; }
            //if ($reg['medio__tipo_medio__descripcion']){ $medio .= $reg['medio__tipo_medio__descripcion'].' '; }
            //if ($reg['medio__subtipoMedio__descripcion']){ $medio .= $reg['medio__subtipoMedio__descripcion'].' '; }

            //$response['aaData'][$key]['medio_name'] = $medio;
            $response['aaData'][$key]['fecha_limite'] = $reg['fecha_limite']->format('d/m/Y');
            $response['aaData'][$key]['fecha_cierre'] = $reg['fecha_cierre'] ? $reg['fecha_cierre']->format('d/m/Y') : null;
            $response['aaData'][$key]['codigo_user'] = $reg['codigo_user'];
            $response['aaData'][$key]['codigo_user_asignado'] = $reg['codigo_user_asignado'];


            $estados = array('0' => 'Pendiente', '1' => 'En proceso', '2' => 'Cerrada');
            $estado_incidencia = strval($reg['estado_incidencia']);
            $response['aaData'][$key]['estado_incidencia'] = $reg['estado_incidencia'] ? $estados[$estado_incidencia] : null;

            $response['aaData'][$key]['tipo'] = $reg['tipo_string'];

        }

        return new JsonResponse($response);

    }

    /**
     * @Route("/{type}", name="incidencia_list")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="R")
     * @Method("GET")
     */
    public function indexAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/incidencia:index.html.twig', array(
            'type' => $type
        ));
    }

    /**
     * @Route("/{type}/add", name="incidencia_add")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="C")
     * @Method("GET")
     */
    public function addAction($type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Incidencia();
        $entity->setTipo($this->getCodeTypeByUrlType($type));
        $entity->setPais($this->getSessionCountry());
        $entity->setCodigoUser($this->getSessionUser()->getCodigo());

        return $this->render('AppBundle:screens/incidencia:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $this->createForm(new IncidenciaType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/{type}/create", name="incidencia_create")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="C")
     * @Method("POST")
     */
    public function createAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Incidencia();
        $entity->setTipo($this->getCodeTypeByUrlType($type));
        $entity->setPais($this->getSessionCountry());
        $entity->setCodigoUser($this->getSessionUser()->getCodigo());

        $params_original = array('entity' => null);

        $form = $this->createForm(new IncidenciaType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('incidencia_edit', array('id' => $entity->getToken(), 'type' => $type)));
        }

        return $this->render('AppBundle:screens/incidencia:form.html.twig', array(
            'entity' => $entity,
            'type' => $type,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{type}/{id}/edit", name="incidencia_edit", options={"expose"=true})
     * @RequiresPermission(submodule="incidencia_{type}", permissions="R")
     * @Method("GET")
     */
    public function editAction($id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entityQB = $em->getRepository('VallasModelBundle:Incidencia')->getOneByTokenQB($id, array('logs' => null))->addOrderBy('logs.fecha', 'DESC');
        $entity = $entityQB->getQuery()->getOneOrNullResult();

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Incidencia entity.');
        }

        $qbImage = $em->getRepository('VallasModelBundle:ImagenIncidencia')->getAllQueryBuilder()->andWhere('p.incidencia = :ot')->setParameter('ot', $entity->getPkIncidencia());
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('incidencia_img_list');
        $imgPaged->setParam('id', $entity->getToken());

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/incidencia:form.html.twig', array(
            'type' => $this->getTypeUrlByCode($entity->getTipo()),
            'entity' => $entity,
            'form' => $this->createForm(new IncidenciaType(), $entity, array('editable' => $this->checkActionPermissions('incidencia_{type}', 'U')))->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new IncidenciaImagenType(), $firstImg, array('editable' => $this->checkActionPermissions('incidencia_{type}', 'U')))->createView()
        ));
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

    private function getCodeTypeByUrlType($type){
        switch($type){
            case 'fixing': return '0';
            case 'monitoring': return '1';
            case 'installation': return '2';
            case 'lighting': return '3';
            case 'plane': return '4';
            case 'others': return '5';
        }
        return '';
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                //LOG DE INCIDENCIA
                $logAccion = 'Modificación';
                if (!$entity->getPkIncidencia()){
                    $logAccion = 'Creacion';
                }
                if (array_key_exists('entity', $params_original) && $params_original['entity']){

                    if ($params_original['entity']->getEstadoIncidencia() != $entity->getEstadoIncidencia()){
                        $logAccion = 'Cambio de estado';
                        if ($entity->getEstadoIncidencia() == 2){
                            $logAccion = 'Cierre';
                        }
                    }
                }
                $log = new LogIncidencia();
                $log->setPais($this->getSessionCountry());
                $log->setIncidencia($entity);
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
     * @RequiresPermission(submodule="incidencia_{type}", permissions="U")
     * @Route("/{type}/{id}/update", name="incidencia_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entityQB = $em->getRepository('VallasModelBundle:Incidencia')->getOneByTokenQB($id, array('logs' => null))->addOrderBy('logs.fecha', 'DESC');
        $entity = $entityQB->getQuery()->getOneOrNullResult();

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Incidencia entity.');
        }

        $form = $this->createForm(new IncidenciaType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('incidencia_edit', array('id' => $entity->getToken(), 'type' => $type)));
        }


        $qbImage = $em->getRepository('VallasModelBundle:ImagenIncidencia')->getAllQueryBuilder()->andWhere('p.incidencia = :ot')->setParameter('ot', $entity->getPkIncidencia());
        $qbImage->addOrderBy('p.created_at', 'DESC');

        $paginator = $this->get('knp_paginator');
        $imgPaged = $paginator->paginate($qbImage, 1, 1);
        $imgPaged->setUsedRoute('incidencia_img_list');
        $imgPaged->setParam('id', $entity->getToken());

        $firstImg = null;
        if (count($imgPaged) > 0){
            $firstImg = $imgPaged[0];
        }

        return $this->render('AppBundle:screens/incidencia:form.html.twig', array(
            'entity' => $entity,
            'type' => $this->getTypeUrlByCode($entity->getTipo()),
            'form' => $form->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new IncidenciaImagenType(), $firstImg)->createView()
        ));
    }

    /**
     * @Route("/{type}/{id}/delete", name="incidencia_delete", options={"expose"=true})
     * @RequiresPermission(submodule="incidencia_{type}", permissions="D")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($id);
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
     * @Route("/{type}/select", name="incidencia_select")
     * @Method("GET")
     */
    public function selectAction($type)
    {
        return $this->render('AppBundle:screens/incidencia:select.html.twig', array(
            'getVars' => $this->getVar(),
            'type' => $type
        ));
    }

    /**
     * @Route("/{type}/edit-field", name="incidencia_edit_field", options={"expose"=true})
     * @RequiresPermission(submodule="incidencia_{type}", permissions="U")
     * @Method("GET")
     */
    public function editFieldAction(Request $request, $type)
    {

        $em = $this->getDoctrine()->getManager();
        $field_type = $this->getVar('field_type');
        $id = $this->getVar('id');

        if ($id){
            $entity = $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Incidencia entity.');
            }
        }else{
            $entity = null;
        }

        $form = $this->createForm(new IncidenciaFieldType(array('_form_name' => 'incidencia_' . $field_type)), $entity, array('type' => $field_type));

        return $this->render('AppBundle:screens/incidencia:form_update_field.html.twig', array('form' => $form->createView(), 'type'=>$type, 'field_type' => $field_type));

    }

    /**
     * @Route("/{type}/update-field", name="incidencia_update_field")
     * @RequiresPermission(submodule="incidencia_{type}", permissions="U")
     * @Method("POST")
     */
    public function updateFieldAction(Request $request, $type)
    {
        $em = $this->getDoctrine()->getManager();
        $field_type = $this->getVar('field_type');

        $entityAux = new Incidencia();
        $form = $this->createForm(new IncidenciaFieldType(array('_form_name' => 'incidencia_'.$field_type)), $entityAux, array('type' => $field_type));

        if ($request->getMethod() == 'POST'){

            $post = $this->postVar('incidencia_'.$field_type);
            $tokens = $post['tokens'] ? explode(',', $post['tokens']) : array();

            $form->handleRequest($request);

            if (count($tokens) < 1){
                $form->addError(new FormError('Debe seleccionar por lo menos un registro'));
            }

            if ($form->isValid()){

                $qb = $em->getRepository('VallasModelBundle:Incidencia')->getQueryBuilder();
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
                        $state = $entityAux->getEstadoIncidencia();
                        break;
                }

                foreach($entities as $entity){
                    switch($field_type){
                        case 'user':
                            if ($user) $entity->setCodigoUserAsignado($user->getCodigo());
                            break;
                        case 'date_limit':
                            if ($dateLimit) $entity->setFechaLimite($dateLimit);
                            break;
                        case 'state':
                            if ($state) $entity->setEstadoIncidencia($state);
                            break;
                    }
                    $em->persist($entity);
                }

                $em->flush();
                $this->get('session')->getFlashBag()->add('notice', $this->get('translator')->trans('form.notice.saved_success'));
            }

        }

        return $this->render('AppBundle:screens/incidencia:form_update_field.html.twig', array('form' => $form->createView(), 'type' => $type, 'field_type' => $field_type));

    }

    /**
     * @Route("/{type}/{id}/view-log", name="incidencia_view_log", options={"expose"=true})
     * @RequiresPermission(submodule="incidencia_{type}", permissions="R")
     * @Method("GET")
     */
    public function viewLogAction(Request $request, $id, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $entityQB = $em->getRepository('VallasModelBundle:Incidencia')->getOneByTokenQB($id, array('logs' => null))->addOrderBy('logs.fecha', 'DESC');
        $entity = $entityQB->getQuery()->getOneOrNullResult();

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Incidencia entity.');
        }

        return $this->render('AppBundle:screens/incidencia:logs.html.twig', array('entity' => $entity, 'type' => $type));

    }
}