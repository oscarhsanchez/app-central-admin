<?php

namespace AppBundle\Controller;

use AppBundle\Controller\VallasAdminController;
use AppBundle\Form\ReportSubcategoryType;
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
use Vallas\ModelBundle\Entity\ReportSubcategory;


/**
 * ReportSubcategory controller.

 * @Route("/{_locale}/report-subcategories")
 * @Route("/report-subcategories", defaults={"_locale"="es"})
 */
class ReportSubcategoryController extends VallasAdminController
{

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager($id_category)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:ReportSubcategory');
        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'id', 'category__name', 'name'));
        $jsonList->setSearchFields(array('category__name','name'));
        $jsonList->setOrderFields(array('','name'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($repository->getQueryBuilder()->andWhere('p.category = :cat')->setParameter('cat', $id_category));

        return $jsonList;
    }

    /**
     * Returns a list of ReportSubcategory entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/{id_category}/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_featured" = false }, name="report_subcategory_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request, $id_category)
    {

        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('VallasModelBundle:ReportCategory')->getOneByToken($id_category);
        $response = $this->getDatatableManager($category->getId())->getResults();

        /*
        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];
        }
        */

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="report_subcategory_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:screens/report_subcategory:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="report_subcategory_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:ReportSubcategory')->getOneByToken($id);
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find ReportSubcategory entity.');
        }

        return $this->render('AppBundle:screens/report_subcategory:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\ReportSubcategoryType', $entity)->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            $categoryOriginal = clone $entity;

            if ($form->isValid()){

                $post = $this->postVar('report_subcategory');

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
     * @Route("/{id}/update", name="report_subcategory_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:ReportSubcategory')->getOneByToken($id);
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find ReportSubcategory entity.');
        }

        $form = $this->createForm('AppBundle\Form\ReportSubcategoryType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_subcategory_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/report_subcategory:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/delete", name="report_subcategory_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:ReportSubcategory')->getOneByToken($id, array('category' => null));
        $category = $entity->getCategory();

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Category entity.');
        }

        $em->remove($entity);
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'form.notice.delete_success');

        return $this->redirect($this->generateUrl('report_category_edit', array('id' => $category->getToken())));

    }

    /**
     * @Route("/{id_category}/create", name="report_subcategory_create")
     * @Method("POST")
     */
    public function createAction(Request $request, $id_category)
    {

        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('VallasModelBundle:ReportCategory')->getOneByToken($id_category);

        $entity = new ReportSubcategory();
        $entity->setCategory($category);
        $entity->setPais($this->getSessionCountry());
        //$this->initLanguagesForEntity($entity);
        $params_original = array();

        $form = $this->createForm('AppBundle\Form\ReportSubcategoryType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_subcategory_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/report_subcategory:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id_category}/add", name="report_subcategory_add")
     * @Method("GET")
     */
    public function addAction($id_category)
    {

        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('VallasModelBundle:ReportCategory')->getOneByToken($id_category);

        $entity = new ReportSubcategory();
        $entity->setCategory($category);
        $entity->setPais($this->getSessionCountry());

        //$this->initLanguagesForEntity($entity);

        return $this->render('AppBundle:screens/report_subcategory:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\ReportSubcategoryType', $entity)->createView()
        ));
    }



}