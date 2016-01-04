<?php

namespace AppBundle\Form;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\AdminBundle\Form\UserType;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Vallas\ModelBundle\Entity\SecuritySubmodulePermission;

/**
 * Class VallasUserType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class VallasUserType extends UserType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $em = $this->getManager();
        $post = $this->getPost();
        $data = array_key_exists('data', $options) ? $options['data'] : null;
        //$this->prepareRolePermissions($data);
        //$builder->setData($data);

        /*
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($options){
            $data = $options['data'];
            $this->prepareRolePermissions($data);
            $event->setData($data);
        });
        */

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($options){

            $entity = $event->getData();
            $form = $event->getForm();

            if (!$entity) return;

            $form->get('roles')->setData(count($entity->getRoles()) > 0 ? $entity->getRoles()[0] : '');
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options, $em){

            $data = $event->getData();
            $form = $event->getForm();

            if (!$data) return;

            if ($data['roles'] != 'ROLE_CUSTOM'){

                $entity = $options['data'];
                $em->getRepository('VallasModelBundle:SecuritySubmodulePermission')->deleteByUser($entity->getId());
                foreach($form->getData()->getPermissions() as $p){
                    $form->getData()->removePermission($p);
                }
                //$form->getData()->setPermissions(new ArrayCollection());
                //var_dump($form->getData()->getPermissions());
                //exit;
            } else {
                $role = $em->getRepository('VallasModelBundle:Role')->findOneBy(array('code' => 'ROLE_CUSTOM'));
                foreach($form->getData()->getPermissions() as $p){
                    $p->setRole($role);
                }
            }

        });

        $post = $this->getPost();
        $roles = $post ? array($post['roles']) : $data->getRoles();
        if ($data && in_array('ROLE_CUSTOM', $roles)){

            $builder
                ->add('permissions', 'collection', array(
                    'type'           => new RolePermissionType(),
                    'prototype'     => false,
                    'label'          => false,
                    'by_reference'   => true,
                    'allow_delete'   => false,
                    'allow_add'      => false,
                    'options' => array(
                        'required' => true,
                        'user' => $data
                    ),
                ));

        }

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\User',
            'role_class' => 'Vallas\ModelBundle\Entity\Role',
            'allow_extra_fields' => true
        ));
    }

}