<?php

namespace AppBundle\Form;
use Doctrine\Common\Collections\ArrayCollection;
use ESocial\AdminBundle\Form\UserType;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vallas\ModelBundle\Entity\SecuritySubmodulePermission;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class VallasUserPasswordType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class VallasUserPasswordType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        //The password fields must match.

        $builder->add('plainPassword', RepeatedType::class, array(
            'type' => PasswordType::class,
            'invalid_message' => 'form.user.errors.password_mustmatch',
            'options' => array('attr' => array('class' => 'password-field')),
            'required' => true,
            'first_options'  => array('label' => 'form.user.label.password'),
            'second_options' => array('label' => 'form.user.label.password.repeat'),
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'min' => 8,
                    'max' => 40,
                    ))
        )));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\User',
        ));
    }

}