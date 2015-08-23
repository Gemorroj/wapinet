<?php
namespace Wapinet\Bundle\Form\Type\File;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Upload
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
     * @var FormBuilderInterface $builder
     * @var array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('file', 'file_url', array('required' => true, 'label' => false));
        $builder->add('description', 'textarea', array('required' => true, 'label' => 'Описание'));

        // http://view.jquerymobile.com/1.3.2/dist/demos/widgets/autocomplete/autocomplete-remote.html
        // тэги
        $builder->add('tags_string', 'text', array('required' => false, 'label' => 'Тэги через запятую', 'mapped' => false));

        $builder->add('password', 'password', array('required' => false, 'label' => 'Пароль', 'attr' => array('autocomplete' => 'off')));

        if (false === $this->container->get('security.authorization_checker')->isGranted($this->container->getParameter('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', 'captcha', array('required' => true, 'label' => 'Код'));
        }

        $builder->add('submit', 'submit', array('label' => 'Загрузить'));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Wapinet\Bundle\Entity\File',
        ));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'file_upload_form';
    }
}
