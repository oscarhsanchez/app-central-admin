<?php
namespace AppBundle\Form;

use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Form\Widget\SelectableEntityType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AgrupacionMedioType extends ESocialType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;

        $builder
            ->add('descripcion', null, array('label' => 'form.agrupacion_medio.label.descripcion'))
            ->add('tipo', ChoiceType::class, array('label' => 'form.agrupacion_medio.label.tipo', 'placeholder' => 'form.label.choice_empty_value', 'choices' => $this->sf3TransformChoiceOptions(array(
                '0' => 'Landmark',
                '1' => 'Maxivalla Doble'
            ))))
            ->add('coste', null, array('label' => 'form.agrupacion_medio.label.coste'))
            ->add('ubicacion', SelectableEntityType::class, array('label' => 'form.agrupacion_medio.label.ubicacion', 'class' => 'VallasModelBundle:Ubicacion',
                'required' => true,
                'select_text' => 'Select Ubicación',
                'enable_update' => true, 'field_name' => 'pk_ubicacion'));
        ;
    }

    public function getName()
    {
        return 'agrupacion_medio';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\AgrupacionMedio',
        ));
    }

}