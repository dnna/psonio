<?php
namespace Psonio\SiteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', null, array('required' => true, 'label' => 'Προϊόν', 'attr' => array('class' => 'product-typeahead', 'autocomplete' => 'off')))
            ->add('area', null, array('required' => false, 'label' => 'Περιοχή', 'attr' => array('class' => 'area-typeahead', 'autocomplete' => 'off')))
            ->add('search', 'submit', array('label' => 'Αναζήτηση', 'attr' => array('class' => 'btn-lg btn-block')))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Psonio\SiteBundle\Entity\Search',
            'csrf_protection' => false,
        ));
    }

    public function getName()
    {
        return 'search';
    }
}