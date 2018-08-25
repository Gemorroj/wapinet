<?php

namespace App\Form\Type;

use App\Form\DataTransformer\FileUrlDataTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

        $attrFile = ['placeholder' => 'Файл'];
        if ($options['accept']) {
            $attrFile = \array_merge($attrFile, ['accept' => $options['accept']]);
        }

        $builder->add('file', FileType::class, ['attr' => $attrFile, 'label' => false, 'required' => false]);
        $builder->add('url', UrlType::class, ['attr' => ['placeholder' => 'Ссылка'], 'label' => false, 'required' => false]);

        if ($options['delete_button']) {
            $builder->add('file_url_delete', CheckboxType::class, [
                'attr' => ['data-mini' => 'true'],
                'required' => false,
                'label' => 'Удалить',
            ]);
        }

        $builder->addViewTransformer($transformer);
        $this->builder = $builder->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'delete_button' => false,
            'accept' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return FormType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'file_url';
    }
}
