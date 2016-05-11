<?php

namespace AppBundle\Form;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\SecurityBundle\Form\ESocialSecurityUserType;
use ESocial\UtilBundle\Form\ESocialType;
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

        $builder->add('bool_permitir_geo_ubicaciones', null, array('label' => 'form.user.label.bool_permitir_geo_ubicaciones'));
        $builder->add('bool_geo', null, array('label' => 'form.user.label.bool_geo'));

        $builder
            ->add('user_paises', 'collection', array(
                'type'           => new CountrySelectType(),
                'prototype'     => true,
                'label'          => false,
                'by_reference'   => false,
                'allow_delete'   => true,
                'allow_add'      => true,
                'options'        => array('hiddenForm' => true, 'data_class' => 'Vallas\ModelBundle\Entity\UserPais')
            ));

    }

}