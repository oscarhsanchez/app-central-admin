<?php

namespace AppBundle\Form;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\SecurityBundle\Form\ESocialSecurityUserType;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Util;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vallas\ModelBundle\Entity\SecuritySubmodulePermission;

/**
 * Class VallasUserType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class VallasUserType extends ESocialSecurityUserType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $user = array_key_exists('data', $options) ? $options['data'] : null;

        $builder->add('bool_permitir_geo_ubicaciones', null, array('label' => 'form.user.label.bool_permitir_geo_ubicaciones'));
        $builder->add('bool_geo', null, array('label' => 'form.user.label.bool_geo'));

        if ($user && $user->getId()){

            $builder
                ->add('user_paises', CollectionType::class, array(
                    'entry_type' => 'AppBundle\Form\CountrySelectType',
                    'prototype' => true,
                    'label' => false,
                    'by_reference' => false,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'entry_options' => array('hiddenForm' => true, 'data_class' => 'Vallas\ModelBundle\Entity\UserPais')
                ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

    }

    public function getName(){
        return 'user';
    }

    public function getBlockPrefix(){
        return 'user';
    }

}