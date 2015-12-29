<?php

namespace AppBundle\Controller;

use AppBundle\Form\RolePermissionsType;
use ESocial\UtilBundle\Util\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class RoleController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */

/**
 * RoleController.
 * @Route("/{_locale}/role/{id}", defaults={"_locale"="en"})
 */
class RoleController extends VallasAdminController {

    /**
     * @Route("/permissions", name="role_permissions_list", options={"expose"=true})
     * @Method("GET")
     */
    public function indexAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Role')->getOneByToken($id, array('permissions' => null, 'permissions.submodule' => null));

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        return $this->render('AppBundle:screens/role:permissions.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm(new RolePermissionsType(), $entity)->createView()
        ));
    }

    /**
     * @Route("/permissions/update", name="role_permissions_update", options={"expose"=true})
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Role')->getOneByToken($id, array('permissions' => null, 'permissions.submodule' => null));

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        $form = $this->createForm(new RolePermissionsType(), $entity);
        $form->handleRequest($request);

        if ($form->isValid()){
            Database::saveEntity($em, $entity);
            return $this->redirect($this->generateUrl('role_permissions_list', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/role:permissions.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

}