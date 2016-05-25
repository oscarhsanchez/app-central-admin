<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Form\Widget\SelectableEntityType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class ZonaType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class ZonaType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $entity = array_key_exists('data', $options) ? $options['data'] : null;
        $post = $this->getPost();

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($builder){
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->getUser()){
                $form->getData()->setCodigoUser($data->getUser()->getCodigo());
            }

        });

        $builder
            ->add('nombre', null, array('label' => 'form.zona.label.nombre'))
            ->add('user', SelectableEntityType::class, array(
            'label' => 'form.zona.label.user',
            'class' => 'VallasModelBundle:User',
            'required' => false,
            'select_text'   => 'Select User',
            'enable_update' => true
        ));

    }

    public function getName()
    {
        return 'zona';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Zona',
        ));
    }

}