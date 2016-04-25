<?php
namespace VallasSecurityBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use ESocial\UtilBundle\Util\Security;
use ESocial\UtilBundle\Util\Util;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Vallas\ModelBundle\Entity\User;
use VallasSecurityBundle\Annotation\RequiresPermission;
use VallasSecurityBundle\Annotation\AvoidPermission;
use VallasSecurityBundle\Exception\ControllerNotCountryException;
use VallasSecurityBundle\Exception\ControllerNotPermissionException;
use VallasSecurityBundle\Exception\ControllerNotLoggedException;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;

class RequestListener
{

    protected $security_context;

    /**
     * Annotation Reader.
     *
     * @var Reader
     */
    private $annotationReader;
    private $entityManager;
    private $service_container;
    private $router;
    private $templating;

    public function __construct(Container $service_container, $security_context, EntityManager $manager = null, Router $router = null, TwigEngine $templating = null)
    {
        $this->service_container = $service_container;
        $this->security_context = $security_context;
        $this->entityManager = $manager;
        $this->router = $router;
        $this->templating = $templating;
    }

    public function onFilterController(FilterControllerEvent $event)
    {

        $this->entityManager->clear();

        /** @var Request $request */
        $request = $event->getRequest();

        $request_controller = $request->attributes->get('_controller');

        if ($request_controller == 'VallasSecurityBundle:Default:notlogged') return;
        $token = $this->security_context->getToken();
        $user = ($token && $token->isAuthenticated() && $token->getUser() instanceof User)? $token->getUser() : null;

        list($controller, $controller_action) = $event->getController();
        $controller_classname = get_class($controller);
        $arr_data_controller = array('controller_classname' => $controller_classname, 'controller_action' => $controller_action);

        if (!$user) return;
        if (!$event->isMasterRequest()) return;
        if ($controller_classname == 'AppBundle\Controller\DefaultController' && $controller_action == 'indexAction') return;
        if ($controller_classname == 'AppBundle\Controller\CountryController' && $controller_action == 'selectFormAction') return;

        //Control de country
        $session = $request->getSession();
        $vallas_country = $session->get('vallas_country');
        $vallas_country_id = $vallas_country ? $vallas_country['code'] : null;

        if (!$vallas_country_id){
            throw new ControllerNotCountryException('No hay país seleccionado');
            return;
        }

        $permissionCheckingService = $this->service_container->get('vallas.security.permissions.checking');
        $hasPermission = $permissionCheckingService->checkControllerPermission($arr_data_controller);

        if (!$hasPermission){
            throw new ControllerNotPermissionException('No estás autorizado a realizar ésta acción');
            return;

        }

    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {

        $exception = $event->getException();
        $kernel = $event->getKernel();
        $request = $event->getRequest();

        if ($exception instanceof ControllerNotCountryException){

            $session = $request->getSession();
            Security::clearDataSession($session);

            if ($request->isXmlHttpRequest()) {
                $r = $this->router->generate('security_not_country_ajax');
                $response = new RedirectResponse($r);
            } else {

                $r = $this->router->generate('homepage');
                $response = new RedirectResponse($r);
            }
            //$response->setStatusCode(200);
            $event->setResponse($response);
            return;

        } elseif ($exception instanceof ControllerNotPermissionException) {

            $attributes = array(
                '_controller' => $request->isXmlHttpRequest() ? 'VallasSecurityBundle:Default:permissionAjax' : 'VallasSecurityBundle:Default:permission',
                'exception' => $exception,
            );

            $subRequest = $request->duplicate(array(), null, $attributes);
            return $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);


        } elseif ($exception instanceof ControllerNotLoggedException) {

            $attributes = array(
                //'_controller' => 'SeguridadBundle:Default:notlogged',
                'exception' => null,
                '_controller' => 'CuentaBundle:Default:index',
            );

            $session = $request->getSession();
            Security::clearDataSession($session);

            if ($request->isXmlHttpRequest()){

                $r = $this->router->generate('seguridad_need_login');
                $response = new RedirectResponse($r);
                //$response->setStatusCode(200);

                //echo 'a';exit;

                //echo $this->templating->render('SeguridadBundle:Default:no_logeado.html.twig');
                //exit;

                //$response = $this->templating->renderResponse('SeguridadBundle:Default:no_logeado.html.twig');


                /*$attributes = array(
                    //'_controller' => 'SeguridadBundle:Default:notlogged',
                    'exception' => null,
                    '_controller' => 'SeguridadBundle:Default:notlogged',
                );*/

            }else{
                $r = $this->router->generate('user_login');
                $response = new RedirectResponse($r);
                //header("Location: ".$r);
                //exit;
            }


            //$response->setStatusCode(200);
            $event->setResponse($response);
            return;


        }else{
            return;
        }

        $subRequest = $request->duplicate(array(), null, $attributes);
        return $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        //$event->setResponse($response);
    }

}