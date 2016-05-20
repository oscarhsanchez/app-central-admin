<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Util\Dates;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

        $entity = array_key_exists('data', $options) ? $options['data'] : null;
        $post = $this->getPost();

        $id_category = null;
        $id_subcategory = null;

        if ($post){
            $id_category = $post['category'];
        }else{
            $id_category = $entity && $entity->getSubcategory() && $entity->getSubcategory()->getCategory() ? $entity->getSubcategory()->getCategory()->getId() : null;
        }

        $category = $id_category ? $this->getManager()->getRepository('VallasModelBundle:ReportCategory')->find($id_category) : null;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($builder, $category){
            $form = $event->getForm();
            $form->get('category')->setData($category);
        });

        $builder->add('save', SubmitType::class, array('label' => 'form.actions.save'));
        $builder
            ->add('category', EntityType::class, array('mapped'=>false, 'label' => 'form.report_category.label.category', 'class' => 'VallasModelBundle:ReportCategory', 'query_builder' => function($repository) { return $repository->getQueryBuilder(); },
                'choice_label' => 'name', 'placeholder' => 'form.label.choice_empty_value', 'required' => true))
            ->add('subcategory', null, array('label' => 'form.report_category.label.subcategory', 'class' => 'VallasModelBundle:ReportSubcategory', 'query_builder' => function($repository) { return $repository->getQueryBuilder(); },
                'choice_label' => 'name', 'placeholder' => 'form.label.choice_empty_value', 'required' => true))
            ->add('name', null, array('label' => 'form.report.label.name'))
            ->add('jasper_report_id', null, array('label' => 'form.report.label.jasper_report_id'))
            ->add('route', null, array('label' => 'form.report.label.route'));

    }

    public function getName()
    {
        return 'report';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Report',
        ));
    }

}