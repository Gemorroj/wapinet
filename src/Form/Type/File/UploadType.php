<?php

namespace App\Form\Type\File;

use App\Entity\File;
use App\Form\Type\FileUrlType;
use App\Form\Type\TagsType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as CorePasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Upload.
 */
class UploadType extends AbstractType
{
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
     * @var FormBuilderInterface
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', FileUrlType::class, ['required' => true, 'label' => false]);
        $builder->add('description', TextareaType::class, ['required' => true, 'label' => 'Описание']);

        // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/autocomplete/autocomplete-remote.html
        // тэги
        $builder->add('tags', TagsType::class, ['required' => false, 'label' => 'Тэги через запятую', 'attr' => ['autocomplete' => 'off']]);

        $builder->add('plainPassword', CorePasswordType::class, ['required' => false, 'label' => 'Пароль', 'attr' => ['autocomplete' => 'off']]);

        if (false === $this->container->get('security.authorization_checker')->isGranted($this->container->getParameter('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', CaptchaType::class, ['required' => true, 'label' => 'Код']);
        }

        $builder->add('submit', SubmitType::class, ['label' => 'Загрузить']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => File::class,
        ]);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'file_upload_form';
    }
}
