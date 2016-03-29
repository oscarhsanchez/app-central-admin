<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class UbicacionType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class UbicacionType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $em = $this->getManager();
        $post = $this->getPost();

        if (!$options['formMedios']) {

            $builder
                ->add('pk_ubicacion', null, array('label' => 'form.ubicacion.label.pk_ubicacion', 'constraints' => array(new NotBlank())))
                ->add('empresa', 'entity', array(
                    'label' => 'form.ubicacion.label.empresa',
                    'class' => 'VallasModelBundle:Empresa',
                    'empty_value' => 'form.label.choice_empty_value',
                    'choice_label' => 'rfc', 'required' => false,
                ))
                ->add('plaza', 'entity', array(
                    'label' => 'form.ubicacion.label.plaza',
                    'class' => 'VallasModelBundle:Plaza',
                    'empty_value' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre', 'required' => false,
                ))
                ->add('zona_fijacion', 'entity', array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_fijacion',
                    'class' => 'VallasModelBundle:Zona',
                    'empty_value' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 0');
                    },
                ))
                ->add('zona_iluminacion', 'entity', array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_iluminacion',
                    'class' => 'VallasModelBundle:Zona',
                    'empty_value' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 1');
                    },
                ))
                ->add('zona_instalacion', 'entity', array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_instalacion',
                    'class' => 'VallasModelBundle:Zona',
                    'empty_value' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 2');
                    },
                ))
                ->add('unidad_negocio', null, array('label' => 'form.ubicacion.label.unidad_negocio'))
                ->add('tipo_medio', null, array('label' => 'form.ubicacion.label.tipo_medio'))
                ->add('estatus', null, array('label' => 'form.ubicacion.label.estatus'))
                ->add('ubicacion', null, array('label' => 'form.ubicacion.label.ubicacion', 'constraints' => array(new NotBlank())))
                ->add('direccion_comercial', null, array('label' => 'form.ubicacion.label.direccion_comercial'))
                ->add('referencia', null, array('label' => 'form.ubicacion.label.referencia'))
                ->add('trafico_vehicular', null, array('label' => 'form.ubicacion.label.trafico_vehicular'))
                ->add('trafico_transeuntes', null, array('label' => 'form.ubicacion.label.trafico_transeuntes'))
                ->add('nivel_socioeconomico', null, array('label' => 'form.ubicacion.label.nivel_socioeconomico'))
                ->add('lugares_cercanos', null, array('label' => 'form.ubicacion.label.lugares_cercanos'))
                ->add('categoria', null, array('label' => 'form.ubicacion.label.categoria'))
                ->add('catorcena', null, array('label' => 'form.ubicacion.label.catorcena'))
                ->add('anio', null, array('label' => 'form.ubicacion.label.anio', 'constraints' => array(new NotBlank())))
                ->add('fecha_instalacion', 'date', array('required' => false, 'label' => 'form.ubicacion.label.fecha_instalacion', 'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
                ->add('observaciones', 'textarea', array('required' => false, 'label' => 'form.ubicacion.label.observaciones', 'attr' => array('rows' => 5)))
                ->add('latitud', null, array('label' => 'form.ubicacion.label.latitud', 'attr' => array('onchange' => 'onUbicacionCoordsChange()')))
                ->add('longitud', null, array('label' => 'form.ubicacion.label.longitud', 'attr' => array('onchange' => 'onUbicacionCoordsChange()')))
                ->add('reserva', null, array('label' => 'form.ubicacion.label.reserva'));
        } else {

            $builder->add('medios', 'collection', array(
                'type' => 'selectable_entity',
                'prototype' => true,
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'options' => array(
                    'label' => false,
                    'class' => 'VallasModelBundle:Medio',
                    'select_text' => 'Select Medio',
                    'enable_update' => true
                )
            ));
        }
    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'ubicacion';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Ubicacion',
            'formMedios' => false
        ));
    }

}