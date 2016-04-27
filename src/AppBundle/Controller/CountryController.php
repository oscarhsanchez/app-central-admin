<?php

namespace AppBundle\Controller;

use AppBundle\Form\CountrySelectType;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ESocial\AdminBundle\Annotation\FilterAware;

/**
 * Country controller.
 *
 * @Route("/{_locale}/country", defaults={"_locale"="es"})
 */
class CountryController extends VallasAdminController
{

    /**
     * Returns a list of Packaging entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json"}, name="country_list_json")
     *
     * @Method("GET")
     */
    public function listJsonAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('VallasModelBundle:Pais');
        /** @var EntityJsonList $jsonList */
        $jsonList = new EntityJsonList($this->getRequest(), $this->getDoctrine()->getManager());

        $qb = $repository->getAllQueryBuilder();
        //image
        $jsonList->setFieldsToGet(array('pk_pais', 'nombre', 'token'));
        $jsonList->setSearchFields(array('nombre'));
        $jsonList->setRepository($repository);
        $jsonList->setQueryBuilder($qb);

        $response = $jsonList->getResults();

        return new JsonResponse($response);

    }

    /**
     * @FilterAware(disableFilter="country_filter")
     * @Route("/selectForm", name="country_select_form")
     */
    public function selectFormAction(Request $request)
    {

        $boolRedirect = false;
        $form = $this->createForm(new CountrySelectType(), null, array('user' => $this->getSessionUser()));

        if ($request->getMethod() == 'POST'){
            $form->handleRequest($request);
            if ($form->isValid()){

                $post = $form->getData();
                $country = $post['pais'];
                $session = $request->getSession();
                $session->set('vallas_country', array('code' => $country->getPkPais(), 'name' => $country->getNombre()));

                $boolRedirect = true;

            }
        }

        return $this->render('AppBundle:screens/country:form_select.html.twig', array(
            'form' => $form->createView(),
            'boolRedirect' => $boolRedirect
        ));
    }

    /**
     * @FilterAware(disableFilter="country_filter")
     * @Route("/select", name="country_select")
     * @Method("GET")
     */
    public function selectAction()
    {

        $em = $this->getDoctrine()->getManager();

        return $this->render('AppBundle:screens/country:select.html.twig', array(
            'getVars' => $this->getVar()
        ));
    }
}
