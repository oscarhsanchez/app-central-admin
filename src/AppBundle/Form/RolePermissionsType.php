<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class RolePermissionsType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class RolePermissionsType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('permissions', 'collection', array(
                'type'           => new RolePermissionType(),
                'prototype'     => false,
                'label'          => false,
                'by_reference'   => true,
                'allow_delete'   => false,
                'allow_add'      => false,
                'options' => array(
                    'required' => true
                ),
            ));
    }

    public function getName()
    {
        return 'role';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Role',
        ));
    }

}