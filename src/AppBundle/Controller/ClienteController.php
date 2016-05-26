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
 * Cliente controller.

 * @Route("/{_locale}/cliente")
 * @Route("/cliente", defaults={"_locale"="es"})
 */
class ClienteController extends VallasAdminController
{

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Cliente');
        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'razon_social', 'nombre_comercial'));
        $jsonList->setSearchFields(array('razon_social', 'nombre_comercial'));
        $jsonList->setOrderFields(array('razon_social', 'nombre_comercial'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($repository->getQueryBuilder()->andWhere('p.estado > 0'));

        return $jsonList;
    }

    /**
     * Returns a list of Cliente entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="cliente_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request)
    {
        $response = $this->getDatatableManager()->getResults();

        return new JsonResponse($response);

    }

    /**
     * @Route("/select", name="cliente_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/cliente:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }
}