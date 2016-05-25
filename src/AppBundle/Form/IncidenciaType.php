<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use ESocial\UtilBundle\Form\Widget\SelectableEntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class IncidenciaType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class IncidenciaType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $em = $this->getManager();
        $post = $this->getPost();

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($options, $builder, $em, $data){
            $data = $event->getData();
            $form = $event->getForm();


            if ($data && $data->getCodigoUser()) {
                $user = $em->getRepository('VallasModelBundle:User')->findOneBy(array('codigo' => $data->getCodigoUserAsignado()));
                $form->get('user')->setData($user);
            }


        });

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($options, $builder) {
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->getEstadoIncidencia() != 2){
                $form->getData()->setFechaCierre(null);
                $form->getData()->setObservacionesCierre(null);
            }

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options, $builder, $em){
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data) return;

            if ($form->getData() && $data && $data['user']) {
                $user = $em->getRepository('VallasModelBundle:User')->find($data['user']);
                if ($user) $form->getData()->setCodigoUserAsignado($user->getCodigo());
            }

            if (intval($data['estado_incidencia']) == 2 && $form->getData()->getEstadoIncidencia() != intval($data['estado_incidencia'])) {
                ESocialType::addOptionsToEmbedFormField($builder, $form, 'fecha_cierre', array('constraints' => array(new NotBlank())));
            }

        });

        $builder->add('save', SubmitType::class, array('label' => 'form.actions.save'));
        $builder->add('medio', SelectableEntityType::class, array(
                'label' => 'form.incidencia.label.medio',
                'class' => 'VallasModelBundle:Medio',
                'required' => false,
                'select_text'   => 'Select Medio',
                'enable_update' => true
        ));

        $builder->add('user', EntityType::class, array(
            'mapped' => false,
            'constraints' => array(new NotBlank()),
            'label' => 'form.incidencia.label.user_assigned',
            'placeholder' => 'form.label.choice_empty_value',
            'class' => 'VallasModelBundle:User', 'required' => true,
            'query_builder' => function ($repository){ return $repository->getQueryBuilder()->leftJoin('u.user_paises', 'up'); })
        );

        $builder->add('fecha_limite', DateType::class, array('label' => 'form.incidencia.label.fecha_limite', 'widget' => 'single_text',
            'format' => 'dd/MM/yyyy', 'constraints' => array(new NotBlank()), 'attr' => array('class' => 'calendar text-date')));

        $builder
            ->add('estado_incidencia', ChoiceType::class, array('label' => 'form.incidencia.label.estado_incidencia', 'placeholder' => 'form.label.choice_empty_value', 'choices' => self::sf3TransformChoiceOptions(array(
            '0' => 'form.incidencia.label.estado_incidencia.pendiente', '1' => 'form.incidencia.label.estado_incidencia.en_proceso', '2' => 'form.incidencia.label.estado_incidencia.cerrada')), 'constraints' => array(new NotBlank())));

        $builder->add('observaciones', TextareaType::class, array('label' => 'form.incidencia.label.observaciones', 'required' => false, 'attr' => array('rows' => 5)));

        $estadoIncidencia = $data->getEstadoIncidencia();
        if ($post) $estadoIncidencia = $post['estado_incidencia'];
        if ($estadoIncidencia == 2){
            $builder
                ->add('fecha_cierre', DateType::class, array('label' => 'form.incidencia.label.fecha_cierre', 'widget' => 'single_text', 'required' => true,
                    'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
                ->add('observaciones_cierre', TextareaType::class, array('label' => 'form.incidencia.label.observaciones_cierre', 'required' => false));
        }

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'incidencia';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Incidencia'));
    }

}