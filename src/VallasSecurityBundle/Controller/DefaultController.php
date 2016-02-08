<?php

namespace VallasSecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Default controller.
 *
 * @Route("/{_locale}/security", defaults={"_locale"="en"})
 */
class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('VallasSecurityBundle:Default:index.html.twig', array('name' => $name));
    }

    public function permissionAction(){

        //parche
        $content = $this->renderView('VallasSecurityBundle:Default:permission.html.twig');
        echo $content;
        exit;
        //fin parche

        //IMPORTANTE: el funcionamiento ideal es devolver un objeto response, de cualquiera de las dos maneras siguiente, pero devuelve un error 500,
        //y no hemos conseguido que no devuelva el error 500. incluso cambiando el status code y las cabeceras
        return new Response($content);
        return $this->render('VallasSecurityBundle:Default:permission.html.twig');

    }

    public function permissionAjaxAction(){

        //parche
        $content = $this->renderView('VallasSecurityBundle:Default:permissionAjax.html.twig');
        echo $content;
        exit;
        //fin parche

        //IMPORTANTE: el funcionamiento ideal es devolver un objeto response, de cualquiera de las dos maneras siguiente, pero devuelve un error 500,
        //y no hemos conseguido que no devuelva el error 500. incluso cambiando el status code y las cabeceras
        return new Response($content);
        return $this->render('VallasSecurityBundle:Default:permission.html.twig');

    }

    /**
     * @Route("/notCountry", name="security_not_country_ajax")
     */
    public function notCountryAjaxAction(){
        $content = $this->renderView('VallasSecurityBundle:Default:notCountryAjax.html.twig');
        return new Response($content);
    }
}
