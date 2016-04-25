<?php

namespace VallasSecurityBundle\Services;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\Security\Core\SecurityContext;
use Vallas\ModelBundle\Entity\User;

class SecurityPermissionChecking {

    protected $entityManager;
    /**
     * Annotation Reader.
     *
     * @var Reader
     */
    protected $annotationReader;
    protected $session;
    protected $security_context;

    /** @var  Request */
    protected $request;

    public function __construct(SecurityContext $security_context, EntityManager $em, Reader $reader, RequestStack $request_stack)
    {
        $this->entityManager   = $em;
        $this->annotationReader          = $reader;
        $this->security_context = $security_context;
        $this->request = $request_stack->getCurrentRequest();
    }

    public function checkControllerPermission($arr_data_controller){

        //$request_controller = $request->attributes->get('_controller');
        //$arr_request_controller = explode("::", $request_controller);
        $controller_classname = $arr_data_controller['controller_classname'];
        $controller_action = $arr_data_controller['controller_action'];

        $reflectionClass = new \ReflectionClass($controller_classname);
        $reflectionMethod = $reflectionClass->getMethod($controller_action);

        $avoidAnnotation = $this->annotationReader->getMethodAnnotation($reflectionMethod, 'VallasSecurityBundle\Annotation\AvoidPermission');
        $hasPermissionAnnotations = $this->annotationReader->getMethodAnnotation($reflectionMethod, 'VallasSecurityBundle\Annotation\RequiresPermission');

        if ($hasPermissionAnnotations){

            $allMethodAnnotations = $this->annotationReader->getMethodAnnotations($reflectionMethod);
            $hasPermission = false;
            foreach($allMethodAnnotations as $methodAnnotation){
                if (get_class($methodAnnotation) !== 'VallasSecurityBundle\Annotation\RequiresPermission') continue;

                $required_submodule = $methodAnnotation->getSubmodule();
                $required_submodule = $this->cleanSubmoduleName($required_submodule);

                $arr_required_permissions = explode(',', $methodAnnotation->getPermissions());

                $hasPermission = $this->checkPermissionCode($required_submodule, $arr_required_permissions);

            }

            return $hasPermission;

        }

        return true;

    }

    private function cleanSubmoduleName($submodule){
        $required_submodule = $submodule;
        $route_attributes = $this->request->attributes->get('_route_params');
        if (count($route_attributes)>0){
            foreach($route_attributes as $key=>$value){
                $required_submodule = str_replace('{'.$key.'}', $value, $required_submodule);
            }
        }
        return $required_submodule;
    }

    public function checkPermissionCode($submodule, $permissionCodeRequired){

        $token = $this->security_context->getToken();
        $user = ($token && $token->isAuthenticated() && $token->getUser() instanceof User)? $token->getUser() : null;

        if (!$user) return false;

        $user_roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $user_roles)) return true;

        $role = $this->entityManager->getRepository('VallasModelBundle:Role')->findOneBy(array('code' => $user_roles[0]));
        $arrUserPermissions = $this->entityManager->getRepository('VallasModelBundle:SecuritySubmodulePermission')->getAllByRoleAndUser($role->getId(), $user->getId());

        $permissionCodesRequired = is_array($permissionCodeRequired) ? $permissionCodeRequired : array($permissionCodeRequired);

        $hasPermission = false;

        $required_submodule = $this->cleanSubmoduleName($submodule);

        if (array_key_exists($required_submodule, $arrUserPermissions)){

            foreach($arrUserPermissions[$required_submodule] as $userPermission){
                if (trim($userPermission) != '' && in_array($userPermission, $permissionCodesRequired)){
                    $hasPermission = true;
                    break;
                }
            }
        }

        return $hasPermission;

    }
}