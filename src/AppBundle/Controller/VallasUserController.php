<?php

namespace AppBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use ESocial\AdminBundle\Controller\UserController;
use ESocial\UtilBundle\Util\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Vallas\ModelBundle\Entity\SecuritySubmodulePermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Vallas\ModelBundle\Entity\User;

/**
 * Class VallasUserController
 * @package AppBundle\Controller
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */

/**
 * VallasUserController.
 * @Route("/{_locale}/user", defaults={"_locale"="en"})
 */
class VallasUserController extends UserController {

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

    private function prepareRolePermissions($entity){

        $em = $this->getDoctrine()->getManager();
        $roles = $entity->getRoles();

        $role = $em->getRepository('VallasModelBundle:Role')->findOneBy(array('code' => $roles[0]));

        $qb = $em->getRepository('VallasModelBundle:SecuritySubmodule')
            ->createQueryBuilder('s')
            ->addSelect('permissions')
            ->leftJoin('s.permissions', 'permissions', 'WITH', 'permissions.role = :role AND (permissions.user IS NULL OR permissions.user = :user)')
            ->setParameter('role', $role->getId())
            ->setParameter('user', $entity->getId())
            ->orderBy('s.name');

        $submodules = $qb->getQuery()->getResult();
        $permissions = new ArrayCollection();

        if (count($submodules) > 0){
            foreach($submodules as $sm){
                $strCRUD = null;
                if (count($sm->getPermissions()) > 0) {
                    $smPermission = $sm->getPermissions()[0];
                    $strCRUD = $smPermission->getPermissions();
                }
                $smPermission = new SecuritySubmodulePermission();
                if ($roles[0] == 'ROLE_CUSTOM'){
                    $smPermission->setUser($entity);
                    $smPermission->setRole($role);
                }
                $smPermission->setSubmodule($sm);
                $smPermission->setPermissions($strCRUD);

                $permissions->add($smPermission);
            }
        }
        $entity->setPermissions($permissions);
    }

    /**
     * @Route("/{token}/update", name="esocial_admin_user_update", options={"expose"=true})
     * @Method("POST")
     */
    public function updateAction(Request $request, $token){

        $em = $this->getDoctrine()->getManager();

        $esocialAdminUserType = $this->getEsocialAdminUserType();
        $entity = $em->getRepository($this->getESocialAdminUserClass())->getOneByToken($token);
        $this->prepareRolePermissions($entity);

        $form = $this->createForm(new $esocialAdminUserType(), $entity, array('data_class' => $this->getESocialAdminUserClass(), 'role_class' => $this->getEsocialAdminRoleClass()));

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);
            if ($form->isValid()){

                $this->get('session')->getFlashBag()->add('notice', 'Los datos del usuario han sido guardados correctamente.');

                Database::saveEntity($em, $entity);


                return $this->redirect($this->generateUrl('esocial_admin_user_edit', array('token' => $entity->getToken())));

            }else{
                $this->get('session')->getFlashBag()->add('error', 'Revise los campos, por favor.');
            }

        }

        return $this->render('ESocialAdminBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    /**
     * @Route("/{token}/edit", name="esocial_admin_user_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editAction($token){

        $em = $this->getDoctrine()->getManager();

        $esocialAdminUserType = $this->getEsocialAdminUserType();
        $entity = $em->getRepository($this->getESocialAdminUserClass())->getOneByToken($token);
        $this->prepareRolePermissions($entity);

        $form = $this->createForm(new $esocialAdminUserType(), $entity, array('data_class' => $this->getESocialAdminUserClass(), 'role_class' => $this->getEsocialAdminRoleClass()));

        return $this->render('ESocialAdminBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    /**
     * @Route("/add", name="esocial_admin_user_add")
     * @Method("GET")
     */
    public function addAction(){

        $em = $this->getDoctrine()->getManager();
        $esocialAdminUserClass = $this->getESocialAdminUserClass();
        $esocialAdminUserType = $this->getEsocialAdminUserType();

        $entity = new $esocialAdminUserClass();
        $entity->addUserPaise($this->getSessionCountry());
        $form = $this->createForm(new $esocialAdminUserType(), $entity, array('data_class' => $this->getESocialAdminUserClass(), 'role_class' => $this->getEsocialAdminRoleClass()));

        return $this->render('ESocialAdminBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));

    }

    /**
     * @Route("/create", name="esocial_admin_user_create")
     * @Method("POST")
     */
    public function createAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $esocialAdminUserClass = $this->getESocialAdminUserClass();
        $esocialAdminUserType = $this->getEsocialAdminUserType();

        $entity = new $esocialAdminUserClass();
        $entity->addUserPaise($this->getSessionCountry());
        $form = $this->createForm(new $esocialAdminUserType(), $entity, array('data_class' => $this->getESocialAdminUserClass(), 'role_class' => $this->getEsocialAdminRoleClass()));

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);
            if ($form->isValid()){

                $this->get('session')->getFlashBag()->add('notice', 'Los datos del usuario han sido guardados correctamente.');
                Database::saveEntity($em, $entity);

                return $this->redirect($this->generateUrl('esocial_admin_user_edit', array('token' => $entity->getToken())));

            }else{
                $this->get('session')->getFlashBag()->add('error', 'Revise los campos, por favor.');
            }

        }

        return $this->render('ESocialAdminBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

}