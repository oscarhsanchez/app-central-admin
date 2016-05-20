<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class SecuritySubmoduleType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class SecuritySubmoduleType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $entity = array_key_exists('data', $options) ? $options['data'] : null;
        $post = $this->getPost();

        $builder
            ->add('code', null, array('label' => 'form.security_submodule.label.code'))
            ->add('name', null, array('label' => 'form.security_submodule.label.name'))
        ;

    }

    public function getName()
    {
        return 'security_submodule';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\SecuritySubmodule',
        ));
    }

}