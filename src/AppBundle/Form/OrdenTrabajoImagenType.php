<?php

namespace AppBundle\Form;
use Symfony\Component\Form\FormBuilderInterface;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class OrdenTrabajoImagenType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class OrdenTrabajoImagenType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('estado_imagen', 'choice', array('label'=>'form.work_order_img.label.estado_imagen', 'choices' => array('0' => 'Pendiente', '1' => 'Validada', '2' => 'Rechazada', '3' => 'Retocar')))
            ->add('nombre', 'uploadable_field', array('label'=>'form.work_order_img.label.nombre', 'type'=>'image', 'image_mapping' => 'orden_trabajo_imagen', 'required' => false))
            ->add('observaciones', null, array('label' => 'form.work_order_img.label.observaciones', 'attr' => array('rows' => 3)))
            ->add('observaciones_cliente', null, array('label' => 'form.work_order_img.label.observaciones_cliente', 'attr' => array('rows' => 3)));

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'work_order_img';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Imagen',
        ));
    }

}