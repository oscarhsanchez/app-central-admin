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

}