<?php
namespace VallasSecurityBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
*@Annotation
*/
class RequiresPermission extends Annotation
{

    protected $submodule;

    protected $permissions;

    public function getSubmodule(){
        return $this->submodule;
    }

    public function getPermissions(){
        return $this->permissions;
    }

}