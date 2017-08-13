<?php
namespace WapinetBundle\Form\Type\Guestbook;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WapinetBundle\Entity\Guestbook;

/**
 * Message
 */
class MessageType extends AbstractType
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

        if (false === $this->container->get('security.authorization_checker')->isGranted($this->container->getParameter('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', CaptchaType::class, array('required' => true, 'label' => 'Код'));
        }
        $builder->add('message', TextareaType::class, array('attr' => array('placeholder' => 'Сообщение'), 'required' => true, 'label' => false));
        $builder->add('submit', SubmitType::class, array('label' => 'Написать'));
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Guestbook::class,
        ));
    }


    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'message_form';
    }
}
