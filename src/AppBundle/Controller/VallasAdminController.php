<?php

namespace AppBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use ESocial\UtilBundle\Controller\ESocialController;

/**
 * VallasAdmin controller.
 */
class VallasAdminController extends ESocialController
{

    public function initLanguagesForEntity($entity){
        if (!$this->container->hasParameter('languages')) return;

        $languages = $this->container->getParameter('languages');
        $translationEntityClass = $entity->getTranslationEntityClass();

        foreach($languages as $k=>$l){
            $translation = $entity->translate($k);
            if (!$translation->getId()){
                $translation = new $translationEntityClass();
                $translation->setLocale($k);
                $entity->addTranslation($translation);
            }
        }

    }

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

    public function getSessionUser()
    { //retorna falso si token no esta autentificado o getUser returns false
        $token = $this->get('security.context')->getToken();
        return ($token->isAuthenticated()) ? $token->getUser() : false;
    }

}