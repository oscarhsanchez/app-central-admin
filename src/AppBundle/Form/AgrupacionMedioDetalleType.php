<?php
namespace AppBundle\Form;

use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Form\Widget\SelectableEntityType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AgrupacionMedioDetalleType extends ESocialType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('medio', SelectableEntityType::class, array(
            'label' => 'form.agrupacion_medio_detalle.label.medio',
            'class' => 'VallasModelBundle:Medio',
            'required' => false,
            'select_text'   => 'Select Medio',
            'enable_update' => true
        ));
        $builder
            ->add('factor_agrupacion', null, array('label' => 'form.agrupacion_medio_detalle.label.factor_agrupacion'));
    }

    public function getName()
    {
        return 'agrupacion_medio_detalle';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\AgrupacionMedioDetalle',
        ));
    }

}