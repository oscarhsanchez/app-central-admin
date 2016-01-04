<?php
namespace VallasSecurityBundle\EventListener;

use ESocial\UtilBundle\Util\Security;
use ESocial\UtilBundle\Util\Util;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Doctrine\Common\Annotations\AnnotationReader;
use Vallas\ModelBundle\Entity\User;
use VallasSecurityBundle\Annotation\RequiresPermission;
use VallasSecurityBundle\Annotation\AvoidPermission;
use VallasSecurityBundle\Exception\ControllerNotPermissionException;
use VallasSecurityBundle\Exception\ControllerNotLoggedException;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class RequestListener
{

    protected $security_context;

    private $annotationReader;
    private $entityManager;
    private $service_container;
    private $router;
    private $templating;

    public function __construct($service_container, $security_context, EntityManager $manager = null, AnnotationReader $annotationReader = null, $router = null, TwigEngine $templating = null)
    {
        $this->service_container = $service_container;
        $this->annotationReader = $annotationReader;
        $this->security_context = $security_context;
        $this->entityManager = $manager;
        $this->router = $router;
        $this->templating = $templating;
    }


    public function onFilterController(FilterControllerEvent $event)
    {

        $this->entityManager->clear();

        $controller = $event->getController();

        /** @var Request $request */
        $request = $event->getRequest();
        $session = $request->getSession();
        $token = $this->security_context->getToken();
        $cont = $controller[0];

        //AppBundle\Controller\DefaultController::indexAction

        if ($request->attributes->get('_controller') == 'VallasSecurityBundle:Default:notlogged'){
            return;
        }

        list($object, $method) = $controller;

        // the controller could be a proxy, e.g. when using the JMSSecuriyExtraBundle or JMSDiExtraBundle
        $className = ClassUtils::getClass($object);

        $reflectionClass = new \ReflectionClass($className);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $user = ($token && $token->isAuthenticated() && $token->getUser() instanceof User)? $token->getUser() : null;

        if (!$user) return;

        if (!$event->isMasterRequest()) return;

        $avoidAnnotation = $this->annotationReader->getMethodAnnotation($reflectionMethod, 'VallasSecurityBundle\Annotation\AvoidPermission');
        $hasPermissionAnnotations = $this->annotationReader->getMethodAnnotation($reflectionMethod, 'VallasSecurityBundle\Annotation\RequiresPermission');

        if ($hasPermissionAnnotations){

            $allMethodAnnotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);

            $user_roles = $user->getRoles();
            $role = $this->entityManager->getRepository('VallasModelBundle:Role')->findOneBy(array('code' => $user_roles[0]));
            $arrUserPermissions = $this->entityManager->getRepository('VallasModelBundle:SecuritySubmodulePermission')->getAllByRoleAndUser($role->getId(), $user->getId());

            //$hasPermission = $this->entityManager->getRepository('ModelBundle:SeguridadUsuarioAccion')->checkRequestUser($request, $this->security_context);
            $requiredPermissions = array();
            $hasPermission = false;
            foreach($allMethodAnnotations as $methodAnnotation){
                if (get_class($methodAnnotation) !== 'VallasSecurityBundle\Annotation\RequiresPermission') continue;

                $required_submodule = $methodAnnotation->getSubmodule();
                $arr_required_permissions = explode(',', $methodAnnotation->getPermissions());

                if (array_key_exists($required_submodule, $arrUserPermissions)){

                    foreach($arrUserPermissions[$required_submodule] as $userPermission){
                        if (trim($userPermission) != '' && in_array($userPermission, $arr_required_permissions)){
                            $hasPermission = true;
                            break;
                        }
                    }

                    if ($hasPermission) break;

                }

            }

            if (!$hasPermission){
                throw new ControllerNotPermissionException('No estás autorizado a realizar ésta acción');
                return;

            }

        }

    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {

        $exception = $event->getException();
        $kernel = $event->getKernel();
        $request = $event->getRequest();

        if ($exception instanceof ControllerNotPermissionException) {

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