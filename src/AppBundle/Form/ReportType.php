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
 * Class ReportType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class ReportType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', null, array('label' => 'form.report.label.name'))
            ->add('jasper_report_id', null, array('label' => 'form.report.label.jasper_report_id'))
            ->add('route', null, array('label' => 'form.report.label.route'));

    }

    public function getName()
    {
        return 'report';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Report',
        ));
    }

}