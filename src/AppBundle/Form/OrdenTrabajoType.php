<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class OrdenTrabajoType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class OrdenTrabajoType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $em = $this->getManager();
        $originalData = array_key_exists('data', $options) ? clone $options['data'] : null;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($options, $builder, $em){
            $data = $event->getData();
            $form = $event->getForm();

            if ($data && $data->getCodigoUser()) {
                $user = $em->getRepository('VallasModelBundle:User')->findOneBy(array('codigo' => $data->getCodigoUser()));
                $form->get('user')->setData($user);
            }

        });

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($options, $builder) {
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->getEstadoOrden() != 2){
                $form->getData()->setFechaCierre(null);
                $form->getData()->setObservacionesCierre(null);
            }

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options, $builder, $em){
            $data = $event->getData();
            $form = $event->getForm();

            if ($form->getData() && $data && $data['user']) {
                $user = $em->getRepository('VallasModelBundle:User')->find($data['user']);
                $form->getData()->setCodigoUser($user->getCodigo());
            }

            if (intval($data['estado_orden']) == 2 && $form->getData()->getEstadoOrden() != intval($data['estado_orden'])){
                ESocialType::addOptionsToEmbedFormField($builder, $form, 'fecha_cierre', array('constraints' => array(new NotBlank())));
            }

        });

        $builder
            ->add('fecha_limite', 'date', array('constraints' => array(new NotBlank()), 'label' => 'form.work_order.label.fecha_limite', 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
            ->add('medio', 'selectable_entity', array(
                'label' => 'form.work_order.label.medio',
                'class' => 'VallasModelBundle:Medio',
                'required' => false,
                'select_text'   => 'Select Medio',
                'enable_update' => true
            ))
            ->add('observaciones', null, array('label' => 'form.work_order.label.observaciones', 'required' => false, 'attr' => array('rows' => 5)))
            ->add('fecha_cierre', 'date', array('label' => 'form.work_order.label.fecha_cierre', 'widget' => 'single_text', 'required' => false,
                'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
            ->add('estado_orden', 'choice', array('constraints' => array(new NotBlank()), 'label' => 'form.work_order.label.estado_orden', 'empty_value' => 'form.label.choice_empty_value', 'choices' => array(
                '0' => 'Pendiente', '1' => 'En proceso', '2' => 'Cerrada')))
            ->add('observaciones_cierre', null, array('label' => 'form.work_order.label.observaciones_cierre', 'required' => false))
            ->add('user', 'entity', array('mapped' => false, 'label' => 'form.work_order.label.user', 'empty_value' => 'form.label.choice_empty_value', 'class' => 'VallasModelBundle:User', 'required' => false,
                    'query_builder' => function ($repository){ return $repository->getQueryBuilder()->leftJoin('u.user_paises', 'up'); }))
            ;

    }

    public function getName()
    {
        return 'work_order';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\OrdenTrabajo',
        ));
    }

}