<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vallas\ModelBundle\Entity\Ubicacion;

/**
 * Class UbicacionType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class UbicacionType extends ESocialType {

    public static $categoria_options = array('A' => 'A', 'AA' => 'AA', 'AAA' => 'AAA');
    public static $nivel_socioeconomico_options = array('A/B'=>'A/B', 'C+'=>'C+', 'C'=>'C', 'D+'=>'D+', 'D'=>'D', 'E'=>'E');

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $em = $this->getManager();
        $post = $this->getPost();

        if (!$options['formMedios']) {

            $builder->add('save', SubmitType::class, array('label' => 'form.actions.save'));
            $builder
                ->add('pk_ubicacion', null, array('label' => 'form.ubicacion.label.pk_ubicacion', 'constraints' => array(new NotBlank())))
                ->add('empresa', EntityType::class, array(
                    'label' => 'form.ubicacion.label.empresa',
                    'class' => 'VallasModelBundle:Empresa',
                    'placeholder' => 'form.label.choice_empty_value',
                    'choice_label' => 'rfc', 'required' => false,
                ))
                ->add('plaza', EntityType::class, array(
                    'label' => 'form.ubicacion.label.plaza',
                    'class' => 'VallasModelBundle:Plaza',
                    'placeholder' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre', 'required' => false,
                ))
                ->add('zona_fijacion', EntityType::class, array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_fijacion',
                    'class' => 'VallasModelBundle:Zona',
                    'placeholder' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 0');
                    },
                ))
                ->add('zona_monitoreo', EntityType::class, array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_monitoreo',
                    'class' => 'VallasModelBundle:Zona',
                    'placeholder' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 1');
                    },
                ))
                ->add('zona_instalacion', EntityType::class, array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_instalacion',
                    'class' => 'VallasModelBundle:Zona',
                    'placeholder' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 2');
                    },
                ))
                ->add('zona_iluminacion', EntityType::class, array(
                    'required' => false, 'label' => 'form.ubicacion.label.zona_iluminacion',
                    'class' => 'VallasModelBundle:Zona',
                    'placeholder' => 'form.label.choice_empty_value',
                    'choice_label' => 'nombre',
                    'query_builder' => function ($repository) {
                        return $repository->getQueryBuilder()->andWhere('p.tipo = 3');
                    },
                ))
                ->add('unidad_negocio', null, array('label' => 'form.ubicacion.label.unidad_negocio'))
                ->add('tipo_medio', null, array('label' => 'form.ubicacion.label.tipo_medio'))
                ->add('estatus', null, array('label' => 'form.ubicacion.label.estatus'))
                ->add('ubicacion', null, array('label' => 'form.ubicacion.label.ubicacion', 'constraints' => array(new NotBlank())))
                ->add('direccion_comercial', null, array('label' => 'form.ubicacion.label.direccion_comercial'))
                ->add('referencia', null, array('label' => 'form.ubicacion.label.referencia'))
                ->add('trafico_vehicular', ChoiceType::class, array('label' => 'form.ubicacion.label.trafico_vehicular', 'choices' => self::sf3TransformChoiceOptions(array('ALTO' => 'form.ubicacion.trafico_vehicular.options.high', 'MEDIO' => 'form.ubicacion.trafico_vehicular.options.medium', 'MODERADO' => 'form.ubicacion.trafico_vehicular.options.moderated'))))
                ->add('trafico_transeuntes', ChoiceType::class, array('label' => 'form.ubicacion.label.trafico_transeuntes', 'choices' => self::sf3TransformChoiceOptions(array('ALTO' => 'form.ubicacion.trafico_transeuntes.options.high', 'MEDIO' => 'form.ubicacion.trafico_transeuntes.options.medium', 'MODERADO' => 'form.ubicacion.trafico_transeuntes.options.moderated'))))
                ->add('nivel_socioeconomico', ChoiceType::class, array('label' => 'form.ubicacion.label.nivel_socioeconomico', 'choices' => self::sf3TransformChoiceOptions(self::$nivel_socioeconomico_options)))
                ->add('lugares_cercanos', null, array('label' => 'form.ubicacion.label.lugares_cercanos'))
                ->add('categoria', ChoiceType::class, array('label' => 'form.ubicacion.label.categoria', 'choices' => self::sf3TransformChoiceOptions(self::$categoria_options)))
                ->add('catorcena', null, array('label' => 'form.ubicacion.label.catorcena'))
                ->add('anio', null, array('label' => 'form.ubicacion.label.anio', 'constraints' => array(new NotBlank())))
                ->add('fecha_instalacion', 'date', array('required' => false, 'label' => 'form.ubicacion.label.fecha_instalacion', 'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
                ->add('observaciones', 'textarea', array('required' => false, 'label' => 'form.ubicacion.label.observaciones', 'attr' => array('rows' => 5)))
                ->add('latitud', null, array('label' => 'form.ubicacion.label.latitud', 'attr' => array('onchange' => 'onUbicacionCoordsChange()')))
                ->add('longitud', null, array('label' => 'form.ubicacion.label.longitud', 'attr' => array('onchange' => 'onUbicacionCoordsChange()')))
                ->add('reserva', null, array('label' => 'form.ubicacion.label.reserva'));
        } else {

            $builder->add('save', SubmitType::class, array('label' => 'form.actions.save'));
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

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Ubicacion',
            'formMedios' => false
        ));
    }

}