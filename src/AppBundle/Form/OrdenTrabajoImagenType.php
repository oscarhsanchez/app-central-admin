<?php

namespace AppBundle\Form;
use Symfony\Component\Form\FormBuilderInterface;
use ESocial\UtilBundle\Form\ESocialType;
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
            ->add('orden_trabajo', 'selectable_entity', array('label' => 'form.work_order_img.label.orden_trabajo', 'class' => 'VallasModelBundle:OrdenTrabajo'))
            ->add('nombre', null, array('label' => 'form.work_order_img.label.nombre'))
            ->add('path', null, array('label' => 'form.work_order_img.label.path'))
            ->add('url', 'uploadable_field', array('label'=>'form.work_order_img.label.url', 'type'=>'image', 'image_mapping' => 'orden_trabajo_imagen', 'required' => false))
            ->add('observaciones', null, array('label' => 'form.work_order_img.label.observaciones', 'attr' => array('rows' => 3)))
            ->add('observaciones_cliente', null, array('label' => 'form.work_order_img.label.observaciones_cliente', 'attr' => array('rows' => 3)));

    }

    public function getName()
    {
        return 'work_order_img';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Imagen',
        ));
    }

}