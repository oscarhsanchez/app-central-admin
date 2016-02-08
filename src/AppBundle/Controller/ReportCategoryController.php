<?php

namespace AppBundle\Controller;

use AppBundle\Controller\VallasAdminController;
use ESocial\UtilBundle\Controller\ESocialController;
use ESocial\UtilBundle\Util\Database;
use Vallas\ModelBundle\Entity\ReportCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Form\ReportCategoryType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;


/**
 * ReportCategory controller.

 * @Route("/{_locale}/report-categories")
 * @Route("/report-categories", defaults={"_locale"="en"})
 */
class ReportCategoryController extends VallasAdminController
{

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:ReportCategory');
        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'id', 'name'));
        $jsonList->setSearchFields(array('name'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($repository->getQueryBuilder());

        return $jsonList;
    }

    /**
     * Returns a list of ReportCategory entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_featured" = false }, name="report_category_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request, $_featured=false)
    {
        $response = $this->getDatatableManager()->getResults();

        /*
        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];
        }
        */

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="report_category_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:screens/report_category:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="report_category_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:ReportCategory')->getOneByToken($id);
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find ReportCategory entity.');
        }

        return $this->render('AppBundle:screens/report_category:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new ReportCategoryType(), $entity)->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            $categoryOriginal = clone $entity;

            if ($form->isValid()){

                $post = $this->postVar('report_category');

                //$uploadable_manager = $this->get('distrinetback.uploadable_file_manager');

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
     * @Route("/{id}/update", name="report_category_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:ReportCategory')->getOneByToken($id);
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find ReportCategory entity.');
        }

        $form = $this->createForm(new ReportCategoryType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_category_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/report_category:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="report_category_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:ReportCategory')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Category entity.');
        }

        $em->remove($entity);
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'form.notice.delete_success');

        return $this->redirect($this->generateUrl('report_category_list'));

    }

    /**
     * @Route("/create", name="report_category_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new ReportCategory();
        $entity->setPais($this->getSessionCountry());
        //$this->initLanguagesForEntity($entity);
        $params_original = array();

        $form = $this->createForm(new ReportCategoryType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_category_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/report_category:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="report_category_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new ReportCategory();
        $entity->setPais($this->getSessionCountry());
        //$this->initLanguagesForEntity($entity);

        return $this->render('AppBundle:screens/report_category:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new ReportCategoryType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/{id}/subcategories", name="report_subcategories_by_category", options={"expose"=true})
     * @Method("GET")
     */
    public function getSubcategoriesByCategoryAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('VallasModelBundle:ReportCategory')->find($id);
        $entities = $this->getDoctrine()->getRepository('VallasModelBundle:ReportSubcategory')->findBy(array('category' => $category->getId()));
        $output = array();
        $output[] = array('id' => '', 'description' => '-- Select --');

        foreach ($entities as $member) {
            $output[] = array(
                'id' => $member->getId(),
                'description' => $member->getName(),
            );

        }

        return new JsonResponse($output);
    }

}