<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\Widget\SelectableEntityType;
use ESocial\UtilBundle\Form\Widget\UploadableFieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UbicacionImagenType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class UbicacionImagenType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('estado_imagen', ChoiceType::class, array('label'=>'form.ubicacion_img.label.estado_imagen', 'choices' => self::sf3TransformChoiceOptions(array('0' => 'Pendiente', '1' => 'Validada', '2' => 'Rechazada', '3' => 'Retocar'))))
            ->add('nombre', UploadableFieldType::class, array('label'=>'form.ubicacion_img.label.nombre', 'type'=>'image', 'image_mapping' => 'ubicacion_imagen', 'required' => false))
            ->add('observaciones', null, array('label' => 'form.ubicacion_img.label.observaciones', 'attr' => array('rows' => 3)))
            ->add('observaciones_cliente', null, array('label' => 'form.ubicacion_img.label.observaciones_cliente', 'attr' => array('rows' => 3)))
            ->add('medio', SelectableEntityType::class, array(
                'label' => 'form.work_order.label.medio',
                'class' => 'VallasModelBundle:Medio',
                'required' => false,
                'select_text'   => 'Select Medio',
                'enable_update' => true
            ));

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'ubicacion_img';
    }

    public function getBlockPrefix()
    {
        return 'ubicacion_img';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\ImagenUbicacion',
        ));
    }

}