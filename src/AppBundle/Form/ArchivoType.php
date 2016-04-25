<?php

namespace AppBundle\Form;
use Symfony\Component\Form\FormBuilderInterface;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ArchivoType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class ArchivoType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('nombre', 'uploadable_field', array('label'=>'form.archivo.label.nombre', 'type'=>'file', 'file_mapping' => 'archivo', 'required' => false))
            ;

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'archivo';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver){

        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Archivo'
        ));
    }


}