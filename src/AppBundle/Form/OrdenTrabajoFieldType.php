<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class OrdenTrabajoFieldType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class OrdenTrabajoFieldType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $type = $options['type'];
        $em = $this->getManager();

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($options, $builder, $em, $type, $data){
            $data = $event->getData();
            $form = $event->getForm();

            if ($data){
                $form->get('tokens')->setData($data->getToken());
            }

            if ($type == 'user'){
                if ($data && $data->getCodigoUser()) {
                    $user = $em->getRepository('VallasModelBundle:User')->findOneBy(array('codigo' => $data->getCodigoUser()));
                    $form->get('user')->setData($user);
                }
            }

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options, $builder, $em, $type){
            $data = $event->getData();
            $form = $event->getForm();

            if ($type == 'user') {
                if ($form->getData() && $data && $data['user']) {
                    $user = $em->getRepository('VallasModelBundle:User')->find($data['user']);
                    $form->getData()->setCodigoUser($user->getCodigo());
                }
            }
        });

        $builder->add('tokens', 'hidden', array('mapped' => false));

        switch($type){
            case 'user':
                $builder->add('user', 'entity', array(
                    'mapped' => false,
                    'label' => 'form.work_order.label.user',
                    'empty_value' => 'form.label.choice_empty_value',
                    'class' => 'VallasModelBundle:User', 'required' => true,
                    'query_builder' => function ($repository){ return $repository->getQueryBuilder()->leftJoin('u.user_paises', 'up'); })
                );
                break;
            case 'date_limit':
                $builder->add('fecha_limite', 'date', array('label' => 'form.work_order.label.fecha_limite', 'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')));
                break;
            case 'state':
                $builder->add('estado_orden', 'choice', array('label' => 'form.work_order.label.estado_orden', 'empty_value' => 'form.label.choice_empty_value', 'choices' => array(
                    '0' => 'Pendiente', '1' => 'En proceso', '2' => 'Cerrada')));
        }

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'work_order';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'type' => null
        ));
    }

}