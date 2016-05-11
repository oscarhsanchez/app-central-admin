<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
        $builder->add('tipo_incidencia', ChoiceType::class, array('label' => 'form.motivo_ordenes_pendientes.label.tipo_incidencia', 'constraints' => array(new NotBlank()),
            'placeholder' => 'form.label.choice_empty_value',
            'choices' => array(
                'form.motivo_ordenes_pendientes.tipo_incidencia.options.fixing' => '0',
                'form.motivo_ordenes_pendientes.tipo_incidencia.options.monitoring' => '1',
                'form.motivo_ordenes_pendientes.tipo_incidencia.options.installation' => '2',
                'form.motivo_ordenes_pendientes.tipo_incidencia.options.lighting' => '3',
                'form.motivo_ordenes_pendientes.tipo_incidencia.options.plane' => '4',
                'form.motivo_ordenes_pendientes.tipo_incidencia.options.others' => '5'
            )));

    }

    public function getName()
    {
        return 'motivo_ordenes_pendientes';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\MotivoOrdenesPendientes',
        ));
    }

}