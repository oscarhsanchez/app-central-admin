<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Form\Widget\SelectableEntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $originalData = array_key_exists('data', $options) ? clone $options['data'] : null;
        $post = $this->getPost();

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

            if ($data->getEstadoOrden() != 4){
                $form->getData()->setMotivoOrdenesPendientes(null);
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

            if (intval($data['estado_orden']) == 4){
                ESocialType::addOptionsToEmbedFormField($builder, $form, 'motivo_ordenes_pendientes', array('constraints' => array(new NotBlank())));
            }

        });

        $arrEstados = array(
            '0' => 'form.work_order.label.estado_orden.pendiente',
            '1' => 'form.work_order.label.estado_orden.en_proceso',
            '2' => 'form.work_order.label.estado_orden.cerrada');

        if ($data && $data->getTipo() == 0){
            $arrEstados['3'] = 'form.work_order.label.estado_orden.pendiente_impresion';
        }

        $arrEstados['4'] = 'form.work_order.label.estado_orden.pendiente_incidencia';

        $builder
            ->add('save', SubmitType::class, array('label' => 'form.actions.save'))
            ->add('fecha_limite', DateType::class, array('constraints' => array(new NotBlank()), 'label' => 'form.work_order.label.fecha_limite', 'widget' => 'single_text',
                'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
            ->add('medio', SelectableEntityType::class, array(
                'label' => 'form.work_order.label.medio',
                'class' => 'VallasModelBundle:Medio',
                'required' => false,
                'select_text'   => 'Select Medio',
                'enable_update' => true
            ))
            ->add('observaciones', null, array('label' => 'form.work_order.label.observaciones', 'required' => false, 'attr' => array('rows' => 5)))
            ->add('fecha_cierre', DateType::class, array('label' => 'form.work_order.label.fecha_cierre', 'widget' => 'single_text', 'required' => false,
                'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
            ->add('estado_orden', ChoiceType::class, array('constraints' => array(new NotBlank()), 'label' => 'form.work_order.label.estado_orden',
                'placeholder' => 'form.label.choice_empty_value', 'choices' => self::sf3TransformChoiceOptions($arrEstados)))
            ->add('observaciones_cierre', null, array('label' => 'form.work_order.label.observaciones_cierre', 'required' => false))
            ->add('user', EntityType::class, array('mapped' => false, 'label' => 'form.work_order.label.user', 'placeholder' => 'form.label.choice_empty_value', 'class' => 'VallasModelBundle:User', 'required' => false,
                    'query_builder' => function ($repository){ return $repository->getQueryBuilder()->leftJoin('u.user_paises', 'up'); }))
            ;

        if ($options['data']->getTipo() == '0' || $options['data']->getTipo() == '1'){
            $builder
            ->add('version', null, array('label' => 'form.work_order.label.version', 'required' => false))
            ->add('campania', null, array('label' => 'form.work_order.label.campania', 'required' => false));
        }

        $estadoIncidencia = $data && $data->getEstadoOrden() ? $data->getEstadoOrden() : null;
        if ($post) $estadoIncidencia = $post['estado_orden'];

        $builder->add('motivo_ordenes_pendientes', SelectableEntityType::class, array(
            'label' => 'form.work_order.label.motivo_ordenes_pendientes',
            'class' => 'VallasModelBundle:MotivoOrdenesPendientes',
            'required' => $estadoIncidencia == 4,
            'select_text'   => 'Select Reason',
            'enable_update' => true
        ));
        $builder->add('motivo_ordenes_pendientes_incidencia', HiddenType::class, array('mapped' => false));
    }

    public function getName()
    {
        return 'work_order';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\OrdenTrabajo',
            ));
    }

}