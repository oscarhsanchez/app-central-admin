<?php

namespace AppBundle\Controller;

use AppBundle\Form\CountrySelectType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Country controller.
 *
 * @Route("/{_locale}/country", defaults={"_locale"="en"})
 */
class CountryController extends VallasAdminController
{
    /**
     * @Route("/select", name="country_select")
     */
    public function selectFormAction(Request $request)
    {

        $boolRedirect = false;
        $form = $this->createForm(new CountrySelectType());

        if ($request->getMethod() == 'POST'){
            $form->handleRequest($request);
            if ($form->isValid()){

                $post = $form->getData();
                $country = $post['country'];
                $session = $request->getSession();
                $session->set('vallas_country', array('code' => $country->getPkPais(), 'name' => $country->getNombre()));

                $boolRedirect = true;

            }
        }

        return $this->render('AppBundle:screens/country:select.html.twig', array(
            'form' => $form->createView(),
            'boolRedirect' => $boolRedirect
        ));
    }
}
