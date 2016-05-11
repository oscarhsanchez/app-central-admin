<?php

namespace AppBundle\Controller;

use AppBundle\Form\RolePermissionsType;
use AppBundle\Form\VallasUserType;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\UtilBundle\Util\Database;
use ESocial\UtilBundle\Util\DataTables\EntityJsonList;
use ESocial\UtilBundle\Util\Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\SecuritySubmodulePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Vallas\ModelBundle\Entity\User;

/**
 * Class RoleController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */

/**
 * RoleController.
 * @Route("/{_locale}/role/{id}", defaults={"_locale"="es"})
 */
class RoleController extends VallasAdminController {

    /**
     * @Route("/permissions", name="role_permissions_list", options={"expose"=true})
     * @Method("GET")
     */
    public function indexAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Role')->getOneByToken($id);
        $entity->setPermissions($this->getRolePermissions($entity));

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        return $this->render('AppBundle:screens/role:permissions.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\RolePermissionsType', $entity)->createView()
        ));
    }

    private function getRolePermissions($role){

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('VallasModelBundle:SecuritySubmodule')
            ->createQueryBuilder('s')
            ->addSelect('permissions')
            ->leftJoin('s.permissions', 'permissions', 'WITH', 'permissions.role = :role AND permissions.user IS NULL')
            ->setParameter('role', $role->getId())
            ->orderBy('s.name');

        $submodules = $qb->getQuery()->getResult();
        $permissions = new ArrayCollection();

        if (count($submodules) > 0){
            foreach($submodules as $sm){
                if (count($sm->getPermissions()) > 0){
                    $smPermission = $sm->getPermissions()[0];
                    $permissions->add($smPermission);
                }else{
                    $smPermission = new SecuritySubmodulePermission();
                    $smPermission->setRole($role);
                    $smPermission->setSubmodule($sm);
                    $permissions->add($smPermission);
                }
            }
        }
        return $permissions;
    }

    /**
     * @Route("/permissions/update", name="role_permissions_update", options={"expose"=true})
     * @Method("POST")
     */
    public function updateAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('VallasModelBundle:Role')->getOneByToken($id);
        $entity->setPermissions($this->getRolePermissions($entity));

        if (!$entity){
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        $form = $this->createForm('AppBundle\Form\RolePermissionsType', $entity);
        $form->handleRequest($request);

        if ($form->isValid()){
            foreach($entity->getPermissions() as $permission){
                $permission->setPais($this->getSessionCountry());
            }
            Database::saveEntity($em, $entity);
            return $this->redirect($this->generateUrl('role_permissions_list', array('id' => $entity->getToken())));
        }

        return $this->render('AppBundle:screens/role:permissions.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));
    }

    /**
     * Returns a list of Report entities in JSON format.
     *
     * @return JsonResponse
     * @Route("/async/list.{_format}", requirements={ "_format" = "json" }, defaults={ "_format" = "json" }, name="user_permission_by_role_list_json", options={"expose"=true})
     *
     * @Method("GET")
     */
    public function listJsonAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('VallasModelBundle:Role')->findOneBy(array('code' => $id));
        $entity->setPermissions($this->getRolePermissions($entity));

        $data = array();
        foreach($entity->getPermissions() as $row){
            $rowPermissions = explode(',',$row->getPermissions());
            $data[] = array(
                'submodule' => $row->getSubmodule()->getName(),
                'permission_C' => in_array('C', $rowPermissions),
                'permission_R' => in_array('R', $rowPermissions),
                'permission_U' => in_array('U', $rowPermissions),
                'permission_D' => in_array('D', $rowPermissions),
            );
        }

        return new JsonResponse(array(
            'iTotalRecords'        => count($entity->getPermissions()),
            'iTotalDisplayRecords' => count($entity->getPermissions()),
            'sEcho'                => intval($request->get('sEcho')),
            'aaData'               => $data,
        ));

    }

    /**
     * @Route("/permissions/form", name="user_permissions_form_by_role", options={"expose"=true})
     * @Method("GET")
     */
    public function permissionsFormAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $role = $em->getRepository('VallasModelBundle:Role')->findOneBy(array('code' => $id));

        if (!$role){
            throw $this->createNotFoundException('Unable to find Role entity.');
        }

        $entity = new User();
        $entity->addRole('ROLE_CUSTOM');
        $entity->setPermissions($this->getRolePermissions($role));

        return $this->render('AppBundle:screens/role:permissions_list.html.twig', array(
            'entity' => $entity,
            'form' => $this->createForm('AppBundle\Form\VallasUserType', $entity, array(
                'data_class' => $this->container->getParameter('fos_user.model.user.class'),
                'role_class' => $this->container->getParameter('e_social_admin.role_class')
            ))->createView()
        ));
    }

}