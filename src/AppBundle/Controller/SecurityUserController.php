<?php

namespace AppBundle\Controller;

use AppBundle\Form\VallasUserPasswordType;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\SecurityBundle\Controller\SecurityUserController as BaseSecurityUserController;
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
class SecurityUserController extends BaseSecurityUserController {

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

    /**
     * @Route("/{token}/view-geo", name="user_view_geo", options={"expose"=true})
     * @Method("GET")
     */
    public function viewGeoAction($token){

        $em = $this->getDoctrine()->getManager();

        $fecha = date('Y-m-d');

        if ($fechaParam = $this->getVar('fecha')){
            $fecha = date('Y-m-d', Dates::convertAppStringToDate($fechaParam));
        }

        $entity = $em->getRepository($this->getESocialAdminUserClass())
            ->getOneByTokenQB($token)
            ->addSelect('geo')
            ->leftJoin('e.user_geolocations', 'geo', 'WITH', 'geo.fecha BETWEEN :fechaIni AND :fechaFin')
            ->setParameter('fechaIni', $fecha.' 00:00:00')
            ->setParameter('fechaFin', $fecha.' 23:59:59')
            ->getQuery()
            ->getOneOrNullResult();

        $recorrido =  Util::getJSONArrayFromCollection($entity->getUserGeolocations(), array('fecha','latitud','longitud'));

        foreach($recorrido as $k=>$r){
            $recorrido[$k]['visible'] = true;
        }

        $timeRange = array();
        foreach (range(0,24) as $fullhour){

            if (strlen(strval($fullhour)) < 2){
                $fullhour = "0".$fullhour;
            }
            if (intval($fullhour) < 24){
                $timeRange[] = "$fullhour";
                $timeRange[] = "$fullhour:30";
            }else{
                $timeRange[] = "23:59";
            }

        }

        return $this->render('AppBundle:screens/user:geo.html.twig', array(
            'entity' => $entity,
            'waypoints'=>json_encode( array_values($recorrido) ),
            'recorrido'=> $recorrido,
            'timerange' => $timeRange,
            'dateFormatted' => $fecha,
            'reloading' => $this->getVar('reloading')
        ));

    }

    /**
     * @Route("/{token}/edit/password", name="admin_user_password_edit", options={"expose"=true})
     * @Method("GET")
     */
    public function editPasswordAction($token){

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getESocialAdminUserClass())->getOneByToken($token);

        $form = $this->createForm('AppBundle\Form\VallasUserPasswordType', $entity);

        return $this->render('AppBundle:screens/user:form_password.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    /**
     * @Route("/{token}/update/password", name="admin_user_password_update")
     * @Method("POST")
     */
    public function updatePasswordAction(Request $request, $token){

        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository($this->getESocialAdminUserClass())->getOneByToken($token);
        $form = $this->createForm('AppBundle\Form\VallasUserPasswordType', $entity);

        $boolSaved = false;

        if ($request->getMethod() == 'POST'){

            $form->handleRequest($request);

            if ($form->isValid()){
                Database::saveEntity($em, $entity);
                $boolSaved = true;
            }
        }

        if ($boolSaved){
            $this->get('session')->getFlashBag()->add('notice', $this->get('translator')->trans('form.notice.saved_success'));
            return $this->redirect($this->generateUrl('admin_user_password_edit', array('token' => $entity->getToken())));
        }else{
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('form.notice.saved_error'));
        }

        return $this->render('AppBundle:screens/user:form_password.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    private function initEntity($entity){
        $userPais = new UserPais();
        $userPais->setUser($entity);
        $userPais->setPais($this->getSessionCountry());
        $entity->addUserPaise($userPais);
    }

    public function addAction(){

        $em = $this->getDoctrine()->getManager();
        $esocialAdminUserClass = $this->getESocialAdminUserClass();
        $esocialAdminUserType = $this->getEsocialAdminUserType();

        $entity = new $esocialAdminUserClass();
        $this->initEntity($entity);
        $form = $this->createForm($esocialAdminUserType, $entity);

        return $this->render('ESocialAdminBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView()
        ));

    }

    public function createAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $esocialAdminUserClass = $this->getESocialAdminUserClass();
        $esocialAdminUserType = $this->getEsocialAdminUserType();

        $entity = new $esocialAdminUserClass();
        $this->initEntity($entity);
        $form = $this->createForm($esocialAdminUserType, $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            Database::saveEntity($em, $entity);
            return $this->redirect($this->generateUrl('esocial_admin_user_edit', array('token' => $entity->getToken())));
        }

        return $this->render('ESocialAdminBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    public function updateAction(Request $request, $token){

        $em = $this->getDoctrine()->getManager();

        $esocialAdminUserType = $this->getEsocialAdminUserType();

        $entity = $em->getRepository($this->getESocialAdminUserClass())->getOneByToken($token, array('user_paises' => null));
        $this->prepareRolePermissions($entity);

        $form = $this->createForm($esocialAdminUserType, $entity);

        $boolSaved = $this->saveAction($request, $entity, array(), $form);

        if ($boolSaved){
            Database::saveEntity($em, $entity);
            return $this->redirect($this->generateUrl('esocial_admin_user_edit', array('token' => $entity->getToken())));
        }

        return $this->render('ESocialSecurityBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    public function editAction($token){

        $em = $this->getDoctrine()->getManager();
        $esocialAdminUserType = $this->getEsocialAdminUserType();
        $entity = $em->getRepository($this->getESocialAdminUserClass())->getOneByToken($token, array('user_paises' => null));

        $this->prepareRolePermissions($entity);

        $form = $this->createForm($esocialAdminUserType, $entity);

        return $this->render('ESocialSecurityBundle:screens/user:form.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));

    }

    public function saveAction(Request $request, $entity, $params_original, $form){

        $em = $this->getDoctrine()->getManager();

        if ($request->getMethod() == 'POST'){

            $original_countries = array();
            foreach($entity->getUserPaises() as $up){
                $original_countries[] = $up;
            }

            $form->handleRequest($request);

            if ($form->isValid()){

                foreach($original_countries as $original_key=>$original_up){
                    $boolDelete = true;
                    foreach($entity->getUserPaises() as $key=>$up){
                        if ($key == $original_key){
                            $boolDelete = false;
                            break;
                        }
                    }
                    if ($boolDelete){
                        $entity->removeUserPaise($original_up);
                        $em->remove($original_up);
                    }
                }

                foreach($entity->getUserPaises() as $userPais){
                    $userPais->setUser($entity);
                }

                foreach($entity->getPermissions() as $permission){
                    $permission->setPais($this->getSessionCountry());
                }

                Database::saveEntity($em, $entity);

                $this->get('session')->getFlashBag()->add('notice', $this->get('translator')->trans('form.notice.saved_success'));

                return true;

            }else{
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('form.notice.saved_error'));
                return false;
            }
        }

        return false;

    }

}