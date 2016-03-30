<?php

namespace AppBundle\Controller;

use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MedioController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
/**
 * Medio controller.
 *
 * @Route("/{_locale}/medios", defaults={"_locale"="en"})
 */
class MedioController extends VallasAdminController {

    /**
     * @return EntityJsonList
     */
    private function getDatatableManager()
    {
        $ubicacion = $this->getVar('ubicacion');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Medio');
        $qb = $repository->getAllQueryBuilder()->leftJoin('p.ubicacion', 'ubi')->addOrderBy('ubi.ubicacion', 'ASC')->addOrderBy('p.posicion', 'ASC');

        if ($ubicacion){
            $qb->andWhere('p.ubicacion = :ubicacion')->setParameter('ubicacion', $ubicacion);
        }

        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());
        $jsonList->setFieldsToGet(array('token', 'posicion', 'ubicacion__ubicacion', 'subtipoMedio__descripcion', 'tipo_medio', 'ubicacion__latitud', 'ubicacion__longitud'));
        $jsonList->setSearchFields(array('posicion', 'ubicacion__ubicacion', 'subtipoMedio__descripcion', 'tipo_medio'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        return $jsonList;
    }

    /**
     * Returns a list of Medio entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json", "_all" = "all" }, name="medio_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $response = $this->getDatatableManager()->getResults();

        foreach($response['aaData'] as $key=>$row) {
            $reg = $response['aaData'][$key];

            $toString = '';
            if ($reg['ubicacion__ubicacion']){ $toString .= $reg['ubicacion__ubicacion'].' '; }
            if ($reg['tipo_medio']){ $toString .= $reg['tipo_medio'].' '; }
            if ($reg['subtipoMedio__descripcion']){ $toString .= $reg['subtipoMedio__descripcion'].' '; }

            $response['aaData'][$key]['name'] = $toString;
        }

        return new JsonResponse($response);

    }

    /**
     * @Route("/select", name="medio_select")
     * @Method("GET")
     */
    public function selectAction()
    {
        return $this->render('AppBundle:screens/medio:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }
}