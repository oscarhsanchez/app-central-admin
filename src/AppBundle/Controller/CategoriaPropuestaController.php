<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Util\Database;
use Vallas\ModelBundle\Entity\CategoriaPropuesta;
use Vallas\ModelBundle\Entity\ReportCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;


/**
 * CategoriaPropuesta controller.

 * @Route("/{_locale}/category-proposal")
 * @Route("/category-proposal", defaults={"_locale"="es"})
 */
class CategoriaPropuestaController extends VallasAdminController
{

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:CategoriaPropuesta');
        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'pk_categoria_propuesta', 'nombre', 'estado'));
        $jsonList->setSearchFields(array('nombre'));
        $jsonList->setOrderFields(array('','nombre'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($repository->getQueryBuilder()->andWhere('p.estado > 0'));

        return $jsonList;
    }

    /**
     * Returns a list of CategoriaPropuesta entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="categoria_propuesta_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="categoria_propuesta_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:screens/categoria_propuesta:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="categoria_propuesta_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:CategoriaPropuesta')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find CategoriaPropuesta entity.');
        }

        return $this->render('AppBundle:screens/categoria_propuesta:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\CategoriaPropuestaType', $entity)->createView()
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
     * @Route("/{id}/update", name="categoria_propuesta_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:CategoriaPropuesta')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find CategoriaPropuesta entity.');
        }

        $form = $this->createForm('AppBundle\Form\CategoriaPropuestaType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('categoria_propuesta_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/categoria_propuesta:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="categoria_propuesta_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:CategoriaPropuesta')->getOneByToken($id);

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
     * @Route("/create", name="categoria_propuesta_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {

        $entity = new CategoriaPropuesta();
        $params_original = array();

        $form = $this->createForm('AppBundle\Form\CategoriaPropuestaType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('categoria_propuesta_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/categoria_propuesta:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="categoria_propuesta_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $entity = new CategoriaPropuesta();

        return $this->render('AppBundle:screens/categoria_propuesta:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\CategoriaPropuestaType', $entity)->createView()
        ));
    }

}