<?php

namespace AppBundle\Form;
use Symfony\Component\Form\FormBuilderInterface;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class IncidenciaImagenType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class IncidenciaImagenType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if (!$options['is_popup']){
            $builder->add('save', 'submit', array('label' => 'form.actions.save'));
        }

        $builder
            ->add('estado_imagen', 'choice', array('label'=>'form.incidencia_img.label.estado_imagen', 'choices' => array(
                '0' => 'form.incidencia_img.label.estado_imagen.pendiente',
                '1' => 'form.incidencia_img.label.estado_imagen.validada',
                '2' => 'form.incidencia_img.label.estado_imagen.rechazada',
                '3' => 'form.incidencia_img.label.estado_imagen.retocar')))
            ->add('nombre', 'uploadable_field', array('label'=>'form.incidencia_img.label.nombre', 'type'=>'image', 'image_mapping' => 'incidencia_imagen', 'required' => false))
            ->add('observaciones', null, array('label' => 'form.incidencia_img.label.observaciones', 'attr' => array('rows' => 3)))
            ->add('observaciones_cliente', null, array('label' => 'form.incidencia_img.label.observaciones_cliente', 'attr' => array('rows' => 3)));

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'incidencia_img';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver){

        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\ImagenIncidencia',
            'is_popup' => false
        ));
    }

}