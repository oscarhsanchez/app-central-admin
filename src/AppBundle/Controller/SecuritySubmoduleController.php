<?php

namespace AppBundle\Controller;

use AppBundle\Form\SecuritySubmoduleType;
use AppBundle\Form\ZonaType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\SecuritySubmodule;
use Vallas\ModelBundle\Entity\Zona;

/**
 * Class SecuritySubmoduleController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * SecuritySubmodule controller.
 *
 * @Route("/{_locale}/security-submodules", defaults={"_locale"="es"})
 */
class SecuritySubmoduleController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:SecuritySubmodule');
        $qb = $repository->getAllQueryBuilder();

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'code', 'name', 'active'));
        $jsonList->setSearchFields(array('code', 'name'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of SecuritySubmodule entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="security_submodule_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $request = $this->get('request_stack')->getCurrentRequest();

        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="security_submodule_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/security_submodule:index.html.twig', array(

        ));
    }

    /**
     * @Route("/add", name="security_submodule_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $em = $this->getDoctrine()->getManager();

        $entity = new SecuritySubmodule();

        return $this->render('AppBundle:screens/security_submodule:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\SecuritySubmoduleType', $entity)->createView()
        ));
    }

    /**
     * @Route("/create", name="security_submodule_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new SecuritySubmodule();
        $params_original = array('entity' => null);

        $form = $this->createForm('AppBundle\Form\SecuritySubmoduleType', $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('security_submodule_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/security_submodule:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="security_submodule_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:SecuritySubmodule')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find SecuritySubmodule entity.');
        }

        return $this->render('AppBundle:screens/security_submodule:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\SecuritySubmoduleType', $entity)->createView(),
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
     * @Route("/{id}/update", name="security_submodule_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:SecuritySubmodule')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find SecuritySubmodule entity.');
        }

        $form = $this->createForm('AppBundle\Form\SecuritySubmoduleType', $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('security_submodule_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/security_submodule:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/action/{action}", name="security_submodule_action", options={"expose"=true})
     * @Method("GET")
     */
    public function executeActionAction(Request $request, $id, $action)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:SecuritySubmodule')->getOneByToken($id);
        if ($entity){

            $message = null;
            switch($action){
                case 'activate':
                    $entity->setActive(true);
                    $message = $this->get('translator')->trans('form.notice.updated_success');
                    break;
                case 'deactivate':
                    $entity->setActive(false);
                    $message = $this->get('translator')->trans('form.notice.updated_success');
                    break;
            }
            $em->persist($entity);
            $em->flush($entity);

            return new JsonResponse(array('result' => '1', 'message' => $message));

        }else{

            return new JsonResponse(array('result' => '0', 'message' => $this->get('translator')->trans('form.notice.updated_error')));
        }

    }

}