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
use Vallas\ModelBundle\Entity\MotivoOrdenesPendientes;

/**
 * Class MotivoOrdenesPendientesType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class MotivoOrdenesPendientesType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('descripcion', null, array('label' => 'form.motivo_ordenes_pendientes.label.descripcion', 'constraints' => array(new NotBlank())));
        $builder->add('tipo_incidencia', 'choice', array('label' => 'form.motivo_ordenes_pendientes.label.tipo_incidencia', 'constraints' => array(new NotBlank()),
            'empty_value' => 'form.label.choice_empty_value',
            'choices' => array(
                '0' => 'form.motivo_ordenes_pendientes.tipo_incidencia.options.fixing',
                '1' => 'form.motivo_ordenes_pendientes.tipo_incidencia.options.monitoring',
                '2' => 'form.motivo_ordenes_pendientes.tipo_incidencia.options.installation',
                '3' => 'form.motivo_ordenes_pendientes.tipo_incidencia.options.lighting',
                '4' => 'form.motivo_ordenes_pendientes.tipo_incidencia.options.plane',
                '5' => 'form.motivo_ordenes_pendientes.tipo_incidencia.options.others'
            )));

    }

    public function getName()
    {
        return 'motivo_ordenes_pendientes';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\MotivoOrdenesPendientes',
        ));
    }

}