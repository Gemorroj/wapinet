<?php

namespace Wapinet\UploaderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Wapinet\UploaderBundle\Form\DataTransformer\FileUrlDataTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FileUrlType extends AbstractType
{
    public $builder;
    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $transformer = new FileUrlDataTransformer(
            $this->container,
            $options['required']
        );

        $attrFile = array('placeholder' => 'Файл');
        if ($options['accept']) {
            $attrFile = array_merge($attrFile, array('accept' => $options['accept']));
        }

        $builder->add('file', 'file', array('attr' => $attrFile, 'label' => false, 'required' => false));
        $builder->add('url', 'url', array('attr' => array('placeholder' => 'Ссылка'), 'label' => false, 'required' => false));

        if ($options['delete_button']) {
            $builder->add('file_url_delete', 'checkbox', array(
                'attr' => array('data-mini' => 'true'),
                'required' => false,
                'label' => 'Удалить'
            ));
        }

        $builder->addViewTransformer($transformer);
        $this->builder = $builder->getData();
    }


    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'delete_button' => false,
            'accept' => false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'file_url';
    }
}



