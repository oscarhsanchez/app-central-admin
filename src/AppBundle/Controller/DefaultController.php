<?php

namespace AppBundle\Controller;

use AppBundle\Form\CountrySelectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends VallasAdminController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $session = $request->getSession();
        $vallas_country = $session->get('vallas_country');
        $vallas_country_id = $vallas_country ? $vallas_country['code'] : null;
        $formCountry = $this->createForm('AppBundle\Form\CountrySelectType', null, array('user' => $this->getSessionUser()));

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
            'formCountry' => $formCountry->createView(),
            'boolShowCountryForm' => ($vallas_country_id ? false : true)
        ));
    }


}
