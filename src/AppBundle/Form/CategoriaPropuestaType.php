<?php
namespace AppBundle\Form;

use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CategoriaPropuestaType extends ESocialType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('save', SubmitType::class, array('label' => 'form.actions.save'));
        $builder
            ->add('nombre', null, array('label' => 'form.categoria_propuesta.label.nombre'));
    }

    public function getName()
    {
        return 'categoria_propuesta';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\CategoriaPropuesta',
        ));
    }

}