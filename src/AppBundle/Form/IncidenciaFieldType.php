<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class IncidenciaFieldType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class IncidenciaFieldType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $type = $options['type'];
        $em = $this->getManager();
        $post = $this->getPost();

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($options, $builder, $em, $type, $data){
            $data = $event->getData();
            $form = $event->getForm();

            if ($data){
                $form->get('tokens')->setData($data->getToken());
            }

            if ($type == 'user'){
                if ($data && $data->getCodigoUser()) {
                    $user = $em->getRepository('VallasModelBundle:User')->findOneBy(array('codigo' => $data->getCodigoUserAsignado()));
                    $form->get('user')->setData($user);
                }
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

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options, $builder, $em, $type){
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data) return;

            if ($type == 'user'){
                if ($form->getData() && $data && $data['user']) {
                    $user = $em->getRepository('VallasModelBundle:User')->find($data['user']);
                    $form->getData()->setCodigoUserAsignado($user->getCodigo());
                }
            }

            if ($type == 'state'){
                if (intval($data['estado_incidencia']) == 2 && $form->getData()->getEstadoIncidencia() != intval($data['estado_incidencia'])) {
                    ESocialType::addOptionsToEmbedFormField($builder, $form, 'fecha_cierre', array('constraints' => array(new NotBlank())));
                }
            }
        });

        $builder->add('tokens', 'hidden', array('mapped' => false));

        switch($type){
            case 'user':
                $builder->add('user', EntityType::class, array(
                    'mapped' => false,
                    'label' => 'form.work_order.label.user',
                    'placeholder' => 'form.label.choice_empty_value',
                    'class' => 'VallasModelBundle:User', 'required' => true,
                    'query_builder' => function ($repository){ return $repository->getQueryBuilder()->leftJoin('u.user_paises', 'up'); })
                );
                break;
            case 'date_limit':
                $builder->add('fecha_limite', 'date', array('label' => 'form.work_order.label.fecha_limite', 'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')));
                break;
            case 'state':
                $builder
                    ->add('estado_incidencia', ChoiceType::class, array('label' => 'form.work_order.label.estado_incidencia', 'placeholder' => 'form.label.choice_empty_value',
                        'choices' => self::sf3TransformChoiceOptions(array('0' => 'Pendiente', '1' => 'En proceso', '2' => 'Cerrada'))));

                if ($post['estado_incidencia'] == '2'){
                    $builder
                        ->add('fecha_cierre', 'date', array('label' => 'form.work_order.label.fecha_cierre', 'widget' => 'single_text', 'required' => true,
                            'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
                        ->add('observaciones_cierre', 'textarea', array('label' => 'form.work_order.label.observaciones_cierre', 'required' => false));
                }
                break;
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
            'data_class' => 'Vallas\ModelBundle\Entity\Incidencia',
            'type' => null
        ));
    }

}