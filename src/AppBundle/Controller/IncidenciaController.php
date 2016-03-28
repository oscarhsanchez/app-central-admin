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
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Incidencia');
        $qb = $repository->getAllQueryBuilder()->andWhere('p.estado = 1');

        $tipo = $this->getVar('tipo');
        if ($tipo !== NULL){
            $qb->andWhere('p.tipo = :tipo')->setParameter('tipo', $tipo);
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
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_all" = "all" }, name="incidencia_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {

        $response = $this->getDatatableManager()->getResults();

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
     * @Route("/", name="incidencia_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/incidencia:index.html.twig', array(

        ));
    }

    /**
     * @Route("/add", name="incidencia_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Incidencia();
        $entity->setPais($this->getSessionCountry());
        $entity->setCodigoUser($this->getSessionUser()->getCodigo());

        return $this->render('AppBundle:screens/incidencia:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new IncidenciaType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/create", name="incidencia_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Incidencia();
        $entity->setPais($this->getSessionCountry());
        $entity->setCodigoUser($this->getSessionUser()->getCodigo());

        $params_original = array('entity' => null);

        $form = $this->createForm(new IncidenciaType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('incidencia_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/incidencia:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="incidencia_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($id);

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
            'entity' => $entity,
            'form' => $this->createForm(new IncidenciaType(), $entity)->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new IncidenciaImagenType(), $firstImg)->createView()
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
     * @Route("/{id}/update", name="incidencia_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Incidencia')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Incidencia entity.');
        }

        $form = $this->createForm(new IncidenciaType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('incidencia_edit', array('id' => $entity->getToken())));
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
            'form' => $form->createView(),
            'image' => $firstImg,
            'imgPaged' => $imgPaged,
            'formFirstImage' => $this->createForm(new IncidenciaImagenType(), $firstImg)->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="incidencia_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
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
     * @Route("/select", name="incidencia_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/incidencia:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }

    /**
     * @Route("/edit-field", name="incidencia_edit_field", options={"expose"=true})
     * @Method("GET")
     */
    public function editFieldAction(Request $request)
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

        return $this->render('AppBundle:screens/incidencia:form_update_field.html.twig', array('form' => $form->createView(), 'field_type' => $field_type));

    }

    /**
     * @Route("/update-field", name="incidencia_update_field")
     * @Method("POST")
     */
    public function updateFieldAction(Request $request)
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

        return $this->render('AppBundle:screens/incidencia:form_update_field.html.twig', array('form' => $form->createView(), 'field_type' => $field_type));

    }
}