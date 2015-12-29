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
 * Class RolePermissionType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class RolePermissionType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $post = $options['post'];

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($builder){
            $form = $event->getForm();
            $data = $event->getData();

            $arrPermissions = explode(',', $data->getPermissions());

            $form->get('permission_C')->setData(in_array('C', $arrPermissions));
            $form->get('permission_R')->setData(in_array('R', $arrPermissions));
            $form->get('permission_U')->setData(in_array('U', $arrPermissions));
            $form->get('permission_D')->setData(in_array('D', $arrPermissions));
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($builder, $post){
            $form = $event->getForm();
            $data = $event->getData();

            $arrCRUD = array();
            if (array_key_exists('permission_C', $data) && $data['permission_C'] == '1') $arrCRUD[] = 'C';
            if (array_key_exists('permission_R', $data) && $data['permission_R'] == '1') $arrCRUD[] = 'R';
            if (array_key_exists('permission_U', $data) && $data['permission_U'] == '1') $arrCRUD[] = 'U';
            if (array_key_exists('permission_D', $data) && $data['permission_D'] == '1') $arrCRUD[] = 'D';

            $form->getData()->setPermissions(implode(',', $arrCRUD));
        });


        $builder
            ->add('submodule', 'hidden_entity', array('label' => 'form.role_permissions.label.submodule', 'class' => 'VallasModelBundle:SecuritySubmodule'))
            ->add('permission_C', 'checkbox', array('attr' => array('class' => 'i-checks'), 'label' => false, 'mapped' => false, 'required' => false))
            ->add('permission_R', 'checkbox', array('attr' => array('class' => 'i-checks'), 'label' => false, 'mapped' => false, 'required' => false))
            ->add('permission_U', 'checkbox', array('attr' => array('class' => 'i-checks'), 'label' => false, 'mapped' => false, 'required' => false))
            ->add('permission_D', 'checkbox', array('attr' => array('class' => 'i-checks'), 'label' => false, 'mapped' => false, 'required' => false));

    }

    public function getName()
    {
        return 'role_permissions';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\SecuritySubmodulePermission',
            'post' => array(),
        ));
    }

}