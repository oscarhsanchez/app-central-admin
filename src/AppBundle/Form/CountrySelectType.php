<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class CountrySelectType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class CountrySelectType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $user = array_key_exists('user', $options) ? $options['user'] : null;
        $hiddenForm = array_key_exists('hiddenForm', $options) ? $options['hiddenForm'] : null;

        if ($hiddenForm){
            $builder->add('pais', 'hidden_entity', array(
                'class' => 'VallasModelBundle:Pais',
            ));
        }else{
            $builder->add('pais', EntityType::class, array(
                'label' => 'form.country_select.label.country',
                'class' => 'VallasModelBundle:Pais',
                'placeholder' => 'form.label.choice_empty_value',
                'choice_label' => 'nombre',
                'query_builder' => function($repository) use ($user) {
                    return $user ? $repository->getQueryBuilder()->leftJoin('p.user_paises', 'up')->andWhere('up.user = :user_id')->setParameter('user_id', $user->getId()) : $repository->getQueryBuilder();
                },
            ));
        }

    }

    public function getName(){
        return 'country_select';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'user' => null,
            'hiddenForm' => false
        ));
    }

}