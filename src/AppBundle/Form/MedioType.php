<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vallas\ModelBundle\Entity\Medio;

/**
 * Class MedioType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class MedioType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $entity = array_key_exists('data', $options) ? $options['data'] : null;
        $post = $this->getPost();

        $builder->add('pk_medio', null, array('label' => 'form.medio.label.pk_medio', 'constraints' => array(new NotBlank())));

        $builder->add('subtipoMedio', 'selectable_entity', array(
            'label' => 'form.medio.label.subtipoMedio',
            'class' => 'VallasModelBundle:SubtipoMedio',
            'required' => false,
            'select_text'   => 'Select Subtipo Medio',
            'enable_update' => true
        ));

        $builder->add('posicion', null, array('label' => 'form.medio.label.posicion', 'constraints' => array(new NotBlank())));
        $builder->add('id_cara', null, array('label' => 'form.medio.label.id_cara', 'constraints' => array(new NotBlank())));
        $builder->add('tipo_medio', null, array('label' => 'form.medio.label.tipo_medio', 'attr' => array('maxlength' => 20)));
        $builder->add('estatus_iluminacion', 'choice', array('label' => 'form.medio.label.estatus_iluminacion', 'choices' => array('SI' => 'form.medio.estatus_iluminacion.options.yes', 'NO' => 'form.medio.estatus_iluminacion.options.no')));
        $builder->add('visibilidad', 'choice', array('label' => 'form.medio.label.visibilidad', 'choices' => array('TOTAL' => 'form.medio.visibilidad.options.total', 'PARCIAL' => 'form.medio.visibilidad.options.partial', 'NULA' => 'form.medio.visibilidad.options.null')));
        $builder->add('slots', null, array('label' => 'form.medio.label.slots', 'constraints' => array(new NotBlank())));
        $builder->add('coste', null, array('label' => 'form.medio.label.coste', 'constraints' => array(new NotBlank())));
        $builder->add('estatus_inventario', null, array('label' => 'form.medio.label.estatus_inventario', 'attr' => array('maxlength' => 45)));

    }

    public function getName()
    {
        return 'medio';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Medio',
        ));
    }

}