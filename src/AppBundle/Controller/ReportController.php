<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Controller\ESocialController;
use ESocial\UtilBundle\Util\Database;
use ESocial\UtilBundle\Util\Files;
use Doctrine\Common\Collections\ArrayCollection;
use Vallas\ModelBundle\Entity\Report;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Form\ReportType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;


/**
 * Report controller.
 *
 * @Route("/{_locale}/reports", defaults={"_locale"="en"})
 */
class ReportController extends VallasAdminController
{
    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Report');
        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'id', 'name', ));
        $jsonList->setSearchFields(array('name'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($repository->getAllQueryBuilder());

        return $jsonList;
    }

    /**
     * Returns a list of Report entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="report_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $response = $this->getDatatableManager()->getResults();

        foreach($response['aaData'] as $key=>$row){
            $reg = $response['aaData'][$key];

        }

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="report_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {

        return $this->render('VallasAdminBundle:screens/report:index.html.twig', array(

        ));
    }

    /**
     * @Route("/{id}/edit", name="report_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id, array('translations' => null));
        $this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        return $this->render('VallasAdminBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new ReportType(), $entity)->createView()
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                $post = $this->postVar($form->getName());

                foreach($entity->getCategories() as $c){
                    $entity->removeCategory($c);
                }
                if ($post['category']) $entity->addCategory($em->getRepository('VallasModelBundle:Category')->find($post['category']));
                if ($post['subcategory']) $entity->addCategory($em->getRepository('VallasModelBundle:Category')->find($post['subcategory']));

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
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Report')->getOneByToken($id, array('translations' => null));
        $this->initLanguagesForEntity($entity);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Report entity.');
        }

        $form = $this->createForm(new ReportType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_edit', array('id' => $entity->getToken())));
        }

        return $this->render('VallasAdminBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/create", name="report_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Report();
        $this->initLanguagesForEntity($entity);
        $params_original = array();

        $form = $this->createForm(new ReportType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('report_edit', array('id' => $entity->getToken())));
        }

        return $this->render('VallasAdminBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/add", name="report_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new Report();
        $this->initLanguagesForEntity($entity);

        return $this->render('VallasAdminBundle:screens/report:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new ReportType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/select", name="report_select")
     * @Method("GET")
     */
    public function selectAction(Request $request)
    {

        return $this->render('VallasAdminBundle:screens/report:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }

}