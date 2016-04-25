<?php
namespace AppBundle\Form;

use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class ReportSubcategoryType extends ESocialType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('save', 'submit', array('label' => 'form.actions.save'));
        $builder
            ->add('category', null, array('label' => 'form.report_category.label.category', 'class' => 'VallasModelBundle:ReportCategory', 'query_builder' => function($repository) { return $repository->getQueryBuilder(); },
                'property' => 'name', 'empty_value' => 'form.label.choice_empty_value', 'required' => true))
            ->add('name', null, array('label' => 'form.report_subcategory.label.name'));
    }

    public function getName()
    {
        return 'report_subcategory';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\ReportSubcategory',
        ));
    }

}