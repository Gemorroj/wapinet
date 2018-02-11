<?php
namespace WapinetBundle\Form\Type\Email;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use WapinetBundle\Form\Type\FileUrlType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as CoreEmailType;

/**
 * Email
 */
class EmailType extends AbstractType
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

        $builder->add('to', CoreEmailType::class, array('label' => 'Кому', 'data' => '@'));
        $builder->add('from', CoreEmailType::class, array('label' => 'От кого', 'data' => '@'));
        $builder->add('subject', TextType::class, array('label' => 'Тема'));
        $builder->add('message', TextareaType::class, array('label' => 'Сообщение'));
        $builder->add('file', FileUrlType::class, array('required' => false, 'label' => false));

        if (false === $this->container->get('security.authorization_checker')->isGranted($this->container->getParameter('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', CaptchaType::class, array('required' => true, 'label' => 'Код'));
        }

        $builder->add('submit', SubmitType::class, array('label' => 'Отправить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'email_form';
    }
}
