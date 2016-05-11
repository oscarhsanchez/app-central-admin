<?php

namespace AppBundle\Controller;

use AppBundle\Form\ReportSearchType;
use ESocial\UtilBundle\Controller\ESocialController;
use ESocial\UtilBundle\Util\Database;
use ESocial\UtilBundle\Util\Files;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\UtilBundle\Util\Util;
use Vallas\ModelBundle\Entity\Report;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Form\ReportType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use VallasSecurityBundle\Annotation\RequiresPermission;

/**
 * Report controller.
 *
 * @Route("/{_locale}/reports", defaults={"_locale"="es"})
 */
class ReportController extends VallasAdminController
{
    /**
     * @return EntityJsonList
     */
    private function getDatatableManager($boolActive=false, $category_id=null, $subcategory_id=null)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Report');
        $qb = $repository->getAllQueryBuilder();
        if ($boolActive){
            $qb->andWhere('p.active = 1');
        }
        if ($category_id){
            $qb->leftJoin('p.subcategory', 'subcategory')->leftJoin('subcategory.category', 'category')
                ->andWhere('category = :cat')->setParameter('cat', $category_id);
        }
        if ($subcategory_id){
            $qb->andWhere('p.subcategory = :subcat')->setParameter('subcat', $subcategory_id);
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'id', 'subcategory__category__name', 'subcategory__name', 'name', 'jasper_report_id', 'route', 'active'));
        $jsonList->setSearchFields(array('subcategory__category__name', 'subcategory__name', 'name'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Report entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/{_all}/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_all" = "all" }, name="report_list_json", options={"expose"=true})
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request, $_all)
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $category_id = $request->query->get('category_id', null);
        $subcategory_id = $request->query->get('subcategory_id', null);

        $response = $this->getDatatableManager($_all!='all', $category_id, $subcategory_id)->getResults();

        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

        }

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="report_list")
     * @RequiresPermission(submodule="report", permissions="R")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        return $this->render('AppBundle:screens/report:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="report_edit", options={"expose"=true})
     * @RequiresPermission(submodule="report", permissions="R")
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id);
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        return $this->render('AppBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\ReportType', $entity, array('editable' => $this->checkActionPermissions('report', 'U')))->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                $post = $this->postVar($form->getName());

                $entity->setActive(true);

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
     * @Route("/{id}/update", name="report_update")
     * @RequiresPermission(submodule="report", permissions="U")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id);
        //$this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $form = $this->createForm('AppBundle\Form\ReportType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/create", name="report_create")
     * @RequiresPermission(submodule="report", permissions="C")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Report();
        $entity->setPais($this->getSessionCountry());
        //$this->initLanguagesForEntity($entity);
        $params_original = array();

        $form = $this->createForm('AppBundle\Form\ReportType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="report_add")
     * @RequiresPermission(submodule="report", permissions="C")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Report();
        $entity->setPais($this->getSessionCountry());
        //$this->initLanguagesForEntity($entity);

        return $this->render('AppBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\ReportType', $entity)->createView()
        ));
    }

    /**
     * @Route("/select", name="report_select")
     * @Method("GET")
     */
    public function selectAction(Request $request)
    {

        return $this->render('AppBundle:screens/report:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }

    /**
     * @Route("/{id}/delete", name="report_delete", options={"expose"=true})
     * @RequiresPermission(submodule="report", permissions="D")
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id);
        if ($entity){
            $entity->setActive(0);
            $em->persist($entity);
            $em->flush($entity);
            $this->get('session')->getFlashBag()->add('notice', $this->get('translator')->trans('form.notice.deleted_success'));
        }else{
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('form.notice.deleted_error'));
        }

        return $this->redirect($this->generateUrl('report_list'));
    }

    /**
     * @Route("/{id}/execute", name="report_execute", options={"expose"=true})
     * @RequiresPermission(submodule="report", permissions="R")
     * @Method("GET")
     */
    public function executeAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $reportManager = $this->get('esocial_util.jasper.report_manager');
        $form = $reportManager->getParametersForm($entity->getJasperReportId(), $entity->getRoute(), $this);

        if ($form){
            $formActionPath = $this->generateUrl('report_execute_parameters', array('id' => $entity->getToken()));
            $renderedForm = $reportManager->renderParametersForm($entity->getJasperReportId(), $entity->getRoute(), $formActionPath, $this);
            return $this->render('AppBundle:screens/report:report_parameters_form.html.twig', array(
                'renderedForm' => $renderedForm,
                'report_name' => $entity->getName()
            ));
        }

        $reportManager->getReport($entity->getJasperReportId(), $entity->getRoute(), array(), 'pdf', 'myreport');

    }

    /**
     * @Route("/{id}/execute-parameters", name="report_execute_parameters")
     * @RequiresPermission(submodule="report", permissions="R")
     * @Method("POST")
     */
    public function executeParametersAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $request = $this->get('request_stack')->getCurrentRequest();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id);
        $page = $request->query->get('page', 1);
        $format = $request->query->get('format', 'pdf');

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $reportManager = $this->get('esocial_util.jasper.report_manager');

        $reportExecution = $reportManager->getReportFromForm($entity->getJasperReportId(), $entity->getRoute(), $this);

        $totalPages = $reportExecution['totalPages'];
        $executionId = $reportExecution['requestId'];

        $arrEmpty = array();
        for($i=1;$i<=$totalPages;$i++){ $arrEmpty[] = null; }

        $paginator = $this->get('knp_paginator');
        $rowsPaged = $paginator->paginate($arrEmpty, $page, 1);
        $rowsPaged->setUsedRoute('report_execute_parameters');
        $rowsPaged->setParam('id', $executionId);
        $rowsPaged->setParam('format', 'html');
        $rowsPaged->setParam('totalPages', $totalPages);

        return $this->render('AppBundle:screens/report:report_execution.html.twig', array(
            'reportExecution' => $reportExecution,
            'report_name' => $entity->getName(),
            'format' => 'html',
            'executionId' => $executionId,
            'totalPages' => $totalPages,
            'rowsPaged' => $rowsPaged,
        ));

    }

    /**
     * @Route("/{id}/execute-parameters", name="report_execute_by_request_id")
     * @RequiresPermission(submodule="report", permissions="R")
     * @Method("GET")
     */
    public function executeByRequestIdAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $request = $this->get('request_stack')->getCurrentRequest();
        $page = $request->query->get('page', 1);
        $totalPages = $request->query->get('totalPages', 1);
        $format = $request->query->get('format', 'pdf');

        $reportManager = $this->get('esocial_util.jasper.report_manager');

        if ($id){
            $totalPages = $request->query->get('totalPages', null);
            $reportExecution = $reportManager->getReportByExecutionId($id, $format, $page);

            return $this->render('AppBundle:screens/report:report_execution_page.html.twig', array(
                'reportExecution' => $reportExecution,
            ));

        }

    }

    /**
     * @Route("/list-execution", name="report_execution_list")
     * @RequiresPermission(submodule="report", permissions="R")
     * @Method("GET")
     */
    public function executionListAction(Request $request)
    {

        return $this->render('AppBundle:screens/report:list_for_execution.html.twig', array(
            'searchForm' => $this->createForm('AppBundle\Form\ReportSearchType')->createView()
        ));

    }

}