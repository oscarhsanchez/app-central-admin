<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class ReportSearchType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class ReportSearchType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('category', 'entity', array('label' => 'form.report_category.label.category', 'class' => 'VallasModelBundle:ReportCategory', 'query_builder' => function($repository) { return $repository->getQueryBuilder(); },
                'choice_label' => 'name', 'empty_value' => 'form.label.choice_empty_value'))
            ->add('subcategory', 'choice', array('label' => 'form.report_category.label.subcategory', 'empty_value' => 'form.label.choice_empty_value', 'choices' => array()))
            ;

    }

    public function getName()
    {
        return 'report_search';
    }

}