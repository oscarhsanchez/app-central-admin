<?php

namespace VallasSecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('VallasSecurityBundle:Default:index.html.twig', array('name' => $name));
    }

    public function permissionAction(){

        //parche
        $content = $this->renderView('VallasSecurityBundle:Default:permisos.html.twig');
        echo $content;
        exit;
        //fin parche

        //IMPORTANTE: el funcionamiento ideal es devolver un objeto response, de cualquiera de las dos maneras siguiente, pero devuelve un error 500,
        //y no hemos conseguido que no devuelva el error 500. incluso cambiando el status code y las cabeceras
        return new Response($content);
        return $this->render('VallasSecurityBundle:Default:permisos.html.twig');

    }

    public function permissionAjaxAction(){

        //parche
        $content = $this->renderView('VallasSecurityBundle:Default:permisos_ajax.html.twig');
        echo $content;
        exit;
        //fin parche

        //IMPORTANTE: el funcionamiento ideal es devolver un objeto response, de cualquiera de las dos maneras siguiente, pero devuelve un error 500,
        //y no hemos conseguido que no devuelva el error 500. incluso cambiando el status code y las cabeceras
        return new Response($content);
        return $this->render('VallasSecurityBundle:Default:permisos.html.twig');

    }
}
