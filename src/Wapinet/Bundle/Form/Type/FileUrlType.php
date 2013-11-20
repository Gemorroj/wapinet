<?php

namespace Wapinet\Bundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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

        $required = (isset($options['required']) && true === $options['required']);
        $save = (isset($options['attr']['save']) && true === $options['attr']['save']);
        $saveDirectory = (isset($options['attr']['save_directory']) ? $options['attr']['save_directory'] : null);
        $savePublicDirectory = (isset($options['attr']['save_public_directory']) ? $options['attr']['save_public_directory'] : null);
        $transformer = new FileUrlDataTransformer($this->container, $required, $save, $saveDirectory, $savePublicDirectory);

        $attrFile = array();
        $attrFile = (isset($options['attr']['accept']) ? array_merge($attrFile, array('accept' => $options['attr']['accept'])) : $attrFile);

        $builder->add('file', 'file', array('attr' => $attrFile, 'label' => 'Файл', 'required' => false))
            ->add('url', 'url', array('label' => 'Ссылка', 'required' => false))
            ->addViewTransformer($transformer);
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



