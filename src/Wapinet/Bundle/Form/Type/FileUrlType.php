<?php

namespace Wapinet\Bundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Wapinet\Bundle\Form\DataTransformer\FileUrlDataTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FileUrlType extends AbstractType
{

    /**
     * @var ContainerInterface
     */
    protected $container;

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
            $options['required'],
            $options['save'],
            $options['save_directory'],
            $options['save_public_directory']
        );

        $attrFile = array();
        $attrFile = (isset($options['attr']['accept']) ? array_merge($attrFile, array('accept' => $options['attr']['accept'])) : $attrFile);

        $builder->add('file', 'file', array('attr' => $attrFile, 'label' => 'Файл', 'required' => false));
        $builder->add('url', 'url', array('label' => 'Ссылка', 'required' => false));

        if ($options['delete_button']) {
            $builder->add('file_url_delete', 'checkbox', array(
                'attr' => array('data-mini' => 'true'),
                'required' => false,
                'label' => 'Удалить'
            ));
        }

        $builder->addViewTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'save' => false,
            'save_directory' => null,
            'save_public_directory' => null,
            'delete_button' => false,
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



