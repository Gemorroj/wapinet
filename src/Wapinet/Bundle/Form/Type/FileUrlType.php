<?php

namespace Wapinet\Bundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Wapinet\Bundle\Form\DataTransformer\FileUrlDataTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Vitiko <vitiko@mail.ru>
 */
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
        $transformer = new FileUrlDataTransformer($this->container, $required);

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



