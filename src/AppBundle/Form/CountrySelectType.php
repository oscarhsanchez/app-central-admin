<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class CountrySelectType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class CountrySelectType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('country', 'entity', array('label' => 'form.country_select.label.country', 'class' => 'VallasModelBundle:Pais', 'empty_value' => 'form.label.choice_empty_value', 'choice_label' => 'nombre'));
    }

    public function getName()
{
    return 'country_select';
}

}