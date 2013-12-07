<?php
namespace Wapinet\Bundle\Form\Type\Email;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

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

        $builder->add('to', 'email', array('label' => 'Кому', 'data' => '@'));
        $builder->add('from', 'email', array('label' => 'От кого', 'data' => '@'));
        $builder->add('subject', 'text', array('label' => 'Тема'));
        $builder->add('message', 'textarea', array('label' => 'Сообщение'));
        $builder->add('file', 'file_url', array('required' => false, 'label' => false));

        if (false === $this->container->get('security.context')->isGranted($this->container->getParameter('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', 'captcha', array('required' => true, 'label' => 'Код'));
        }

        $builder->add('submit', 'submit', array('label' => 'Отправить'));
    }

    /**
     * Уникальное имя формы
     *
     * @return string
     */
    public function getName()
    {
        return 'email_form';
    }
}
