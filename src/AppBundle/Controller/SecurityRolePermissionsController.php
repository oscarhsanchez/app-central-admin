<?php

namespace AppBundle\Controller;
use ESocial\AdminBundle\Controller\RoleController;
use ESocial\UtilBundle\Controller\ESocialController;
use ESocial\UtilBundle\Util\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\SecurityBundle\Controller\SecurityRolePermissionsController as BaseSecurityRolePermissionsController;

/**
 * Class SecurityRolePermissionsController
 * @package ESocial\SecurityBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class SecurityRolePermissionsController extends BaseSecurityRolePermissionsController {

    public function getSessionCountry(){

        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request_stack')->getCurrentRequest();
        $session = $request->getSession();
        $vallas_country = $session->get('vallas_country');
        $vallas_country_id = $vallas_country ? $vallas_country['code'] : null;
        if ($vallas_country_id){
            return $em->getRepository('VallasModelBundle:Pais')->find($vallas_country_id);
        }
        return null;
    }

    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $ESocialSecuritySubmodulePermissionsTypeClass = $this->getESocialSecuritySubmodulePermissionsTypeClass();

        $entity = $em->getRepository($this->getEsocialAdminRoleClass())->getOneByToken($id);
        $entity->setPermissions($this->getRolePermissions($entity));

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        $form = $this->createForm($ESocialSecuritySubmodulePermissionsTypeClass, $entity);
        $form->handleRequest($request);

        if ($form->isValid()){
            foreach($entity->getPermissions() as $permission){
                $permission->setPais($this->getSessionCountry());
            }
            Database::saveEntity($em, $entity);
            return $this->redirect($this->generateUrl('role_permissions_list', array('id' => $entity->getToken())));
        }

        return $this->render('ESocialSecurityBundle:screens/role:permissions.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }



}