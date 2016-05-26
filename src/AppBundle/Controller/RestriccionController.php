<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Util\Database;
use Vallas\ModelBundle\Entity\ReportCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Vallas\ModelBundle\Entity\Restriccion;


/**
 * Restriccion controller.

 * @Route("/{_locale}/restriction")
 * @Route("/restriction", defaults={"_locale"="es"})
 */
class RestriccionController extends VallasAdminController
{

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Restriccion');
        $qb = $repository->getQueryBuilder()->andWhere('p.estado > 0');
        $qb
            ->leftJoin('p.cliente', 'cliente')
            ->leftJoin('p.clienteRestriccion', 'clienteRestriccion')
            ->leftJoin('p.categoria', 'categoria')
            ->leftJoin('p.categoriaRestriccion', 'categoriaRestriccion')
            ->leftJoin('p.ubicacion', 'ubicacion');

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'pk_restriccion', 'cliente__razon_social', 'categoria__nombre', 'clienteRestriccion__razon_social', 'categoriaRestriccion__nombre', 'ubicacion__pk_ubicacion', 'ubicacion__ubicacion'));
        $jsonList->setSearchFields(array('cliente__razon_social', 'categoria__nombre', 'clienteRestriccion__razon_social', 'categoriaRestriccion__nombre', 'ubicacion__pk_ubicacion', 'ubicacion__ubicacion'));
        $jsonList->setOrderFields(array('','cliente__razon_social', 'categoria__nombre', 'clienteRestriccion__razon_social', 'categoriaRestriccion__nombre', 'ubicacion__pk_ubicacion', 'ubicacion__ubicacion'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Restriccion entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="restriccion_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="restriccion_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:screens/restriccion:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="restriccion_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Restriccion')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Restriccion entity.');
        }

        return $this->render('AppBundle:screens/restriccion:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\RestriccionType', $entity)->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                $entity->setPais($this->getSessionCountry());

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
     * @Route("/{id}/update", name="restriccion_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Restriccion')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Restriccion entity.');
        }

        $form = $this->createForm('AppBundle\Form\RestriccionType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('restriccion_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/restriccion:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="restriccion_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Restriccion')->getOneByToken($id);

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
     * @Route("/create", name="restriccion_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {

        $entity = new Restriccion();
        $params_original = array();

        $form = $this->createForm('AppBundle\Form\RestriccionType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('restriccion_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/restriccion:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="restriccion_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $entity = new Restriccion();

        return $this->render('AppBundle:screens/restriccion:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\RestriccionType', $entity)->createView()
        ));
    }

}