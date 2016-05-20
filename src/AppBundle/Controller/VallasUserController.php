<?php

namespace AppBundle\Controller;

use AppBundle\Form\VallasUserPasswordType;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\AdminBundle\Controller\UserController;
use ESocial\UtilBundle\Util\Database;
use ESocial\UtilBundle\Util\Dates;
use ESocial\UtilBundle\Util\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\SecuritySubmodulePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Vallas\ModelBundle\Entity\User;
use Vallas\ModelBundle\Entity\UserPais;

/**
 * Class VallasUserController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */

/**
 * VallasUserController.
 * @Route("/{_locale}/user", defaults={"_locale"="es"})
 */
class VallasUserController  {



}