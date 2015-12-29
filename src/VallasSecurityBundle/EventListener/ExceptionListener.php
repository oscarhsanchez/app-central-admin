<?php
namespace VallasSecurityBundle\EventListener;

use VallasSecurityBundle\Exception\ControllerNotPermissionException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ExceptionListener
{
	public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!($exception instanceof ControllerNotPermissionException)) {
            return;
        }

        //$response = new Response($exception->getMessage());
        //$event->setResponse($response);

        $kernel = $event->getKernel();

        /** @var \Symfony\Bundle\FrameworkBundle\HttpKernel $kernel */

        // $exception will be available as a controller argument
        $response  = $kernel->forward('SeguridadBundle:Default:permission', array(
            'exception' => $exception,
        ));

        $event->setResponse($response); // this will stop event propagation

        $attributes = array(
            '_controller' => 'SeguridadBundle:Default:permission',
        );
        $request = $event->getRequest()->duplicate(null, null, $attributes);
        $response = $kernel->handle($request, HttpKernelInterface::SUB_REQUEST, false);
    }
}