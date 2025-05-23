<?php

namespace App\Form\Type;

use App\Form\DataTransformer\FileUrlDataTransformer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileUrlType extends AbstractType
{
    public function __construct(private readonly ParameterBagInterface $parameterBag, private readonly HttpClientInterface $httpClient)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $transformer = new FileUrlDataTransformer(
            $this->parameterBag,
            $this->httpClient,
            $options['required']
        );

        $attrFile = ['placeholder' => 'Файл'];
        if ($options['accept']) {
            $attrFile = \array_merge($attrFile, ['accept' => $options['accept']]);
        }

        $builder->add('file', FileType::class, [
            'attr' => $attrFile,
            'label' => false,
            'required' => false,
        ]);
        $builder->add('url', UrlType::class, [
            'attr' => ['placeholder' => 'Ссылка'],
            'label' => false,
            'required' => false,
            'default_protocol' => 'https',
        ]);

        if ($options['delete_button']) {
            $builder->add('file_url_delete', CheckboxType::class, [
                'attr' => ['data-mini' => 'true'],
                'required' => false,
                'label' => 'Удалить',
            ]);
        }

        $builder->addViewTransformer($transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'delete_button' => false,
            'accept' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'file_url';
    }
}
