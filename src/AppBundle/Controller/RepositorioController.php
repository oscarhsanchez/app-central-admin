<?php

namespace AppBundle\Controller;

use AppBundle\Form\ArchivoType;
use AppBundle\Form\MedioType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Vallas\ModelBundle\Entity\Archivo;
use Vallas\ModelBundle\Entity\Medio;

/**
 * Class ArchivoController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Repositorio controller.
 *
 * @Route("/{_locale}/repositorio", defaults={"_locale"="en"})
 */
class RepositorioController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Archivo');
        $qb = $repository->getAllQueryBuilder()->andWhere('p.estado = 1');

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'nombre', 'path', 'url'));
        $jsonList->setSearchFields(array('nombre', 'path', 'url'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Archivo entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_all" = "all" }, name="archivo_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/", name="repositorio_list")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/repositorio:index.html.twig', array(

        ));
    }

    /**
     * @Route("/add", name="archivo_add")
     * @Method("GET")
     */
    public function addAction()
    {

        $entity = new Archivo();
        $entity->setPais($this->getSessionCountry());

        return $this->render('AppBundle:screens/repositorio:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new ArchivoType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/create", name="archivo_create")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = new Archivo();
        $entity->setPais($this->getSessionCountry());

        $params_original = array('entity' => null);

        $form = $this->createForm(new ArchivoType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, $params_original, $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('archivo_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/repositorio:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/{id}/edit", name="archivo_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Archivo')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Archivo entity.');
        }

        return $this->render('AppBundle:screens/repositorio:form.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new ArchivoType(), $entity)->createView(),
        ));
    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){

                $post = $this->postVar($form->getName());
                $uploadable_manager = $this->get('esocial_util.form.manager.uploadable_file');
                $fileUpload = $uploadable_manager->processUploadedFile($form->get('nombre'), $post['nombre'], array_key_exists('entity', $params_original) && $params_original['entity'] ? $params_original['entity']->getNombre() : null);

                $arrPathInfo = pathinfo($fileUpload);
                $entity->setPath($arrPathInfo['dirname']);
                $entity->setUrl($request->getSchemeAndHttpHost().'/media/archivo/'.$entity->getNombre());

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
     * @Route("/{id}/update", name="archivo_update")
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Archivo')->getOneByToken($id);

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Archivo entity.');
        }

        $form = $this->createForm(new ArchivoType(), $entity);

        $boolSaved = $this->saveAction($request, $entity, array('entity' => clone $entity), $form);

        if ($boolSaved){
            return $this->redirect($this->generateUrl('archivo_edit', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/repositorio:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/delete", name="archivo_delete", options={"expose"=true})
     * @Method("GET")
     */
    public function deleteAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Archivo')->getOneByToken($id);
        if ($entity){
            $entity->setEstado(false);
            $em->persist($entity);
            $em->flush($entity);

            return new JsonResponse(array('result' => '1', 'message' => $this->get('translator')->trans('form.notice.deleted_success')));

        }else{

            return new JsonResponse(array('result' => '0', 'message' => $this->get('translator')->trans('form.notice.deleted_error')));
        }

    }

}