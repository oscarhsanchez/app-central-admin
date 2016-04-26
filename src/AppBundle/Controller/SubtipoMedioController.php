<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Vallas\ModelBundle\Entity\SubtipoMedio;

/**
 * Class SubtipoMedioController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * SubtipoMedio controller.
 *
 * @Route("/{_locale}/subtipoMedios", defaults={"_locale"="en"})
 */
class SubtipoMedioController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $ubicacion = $this->getVar('ubicacion');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:SubtipoMedio');
        $qb = $repository->getAllQueryBuilder();

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'tipoMedio__descripcion', 'descripcion'));
        $jsonList->setSearchFields(array('tipoMedio__descripcion', 'descripcion'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of SubtipoMedio entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="subtipoMedio_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/select", name="subtipoMedio_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/subtipoMedio:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }


}