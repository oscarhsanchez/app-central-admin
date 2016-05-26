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
use Symfony\Component\Validator\Constraints\NotBlank;

class RestriccionType extends ESocialType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($options, $builder){
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->getCliente()){
                ESocialType::addOptionsToEmbedFormField($builder, $form, 'categoriaRestriccion', array('required' => true, 'constraints' => array(new NotBlank())));
                $form->getData()->setCategoria(null);
                $form->getData()->setClienteRestriccion(null);
            }else{
                ESocialType::addOptionsToEmbedFormField($builder, $form, 'clienteRestriccion', array('required' => true, 'constraints' => array(new NotBlank())));
                $form->getData()->setCliente(null);
                $form->getData()->setCategoriaRestriccion(null);
            }

        });

        $builder->add('save', SubmitType::class, array('label' => 'form.actions.save'));
        $builder->add('cliente', SelectableEntityType::class, array(
            'label' => 'form.restriccion.label.cliente',
            'class' => 'VallasModelBundle:Cliente',
            'select_text'   => 'Select Cliente',
            'enable_update' => true
        ));
        $builder->add('clienteRestriccion', SelectableEntityType::class, array(
            'label' => 'form.restriccion.label.clienteRestriccion',
            'class' => 'VallasModelBundle:Cliente',
            'select_text'   => 'Select Cliente',
            'enable_update' => true
        ));
        $builder->add('categoria', SelectableEntityType::class, array(
            'label' => 'form.restriccion.label.categoria',
            'class' => 'VallasModelBundle:CategoriaPropuesta',
            'select_text'   => 'Select Categoría',
            'enable_update' => true
        ));
        $builder->add('categoriaRestriccion', SelectableEntityType::class, array(
            'label' => 'form.restriccion.label.categoriaRestriccion',
            'class' => 'VallasModelBundle:CategoriaPropuesta',
            'select_text'   => 'Select Categoría',
            'enable_update' => true
        ));
        $builder->add('ubicacion', SelectableEntityType::class, array(
            'label' => 'form.restriccion.label.ubicacion',
            'class' => 'VallasModelBundle:Ubicacion',
            'required' => true,
            'constraints' => array(new NotBlank()),
            'select_text' => 'Select Ubicación',
            'enable_update' => true));
    }

    public function getName()
    {
        return 'restriccion';
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Restriccion',
        ));
    }

}