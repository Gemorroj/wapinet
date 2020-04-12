<?php

namespace App\Form\Type\Email;

use App\Form\Type\FileUrlType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as CoreEmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Email.
 */
class EmailType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ParameterBagInterface $parameterBag)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @var FormBuilderInterface
     * @var array
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('to', CoreEmailType::class, ['label' => 'Кому', 'data' => '@']);
        $builder->add('from', CoreEmailType::class, ['label' => 'От кого', 'data' => '@']);
        $builder->add('subject', TextType::class, ['label' => 'Тема']);
        $builder->add('message', TextareaType::class, ['label' => 'Сообщение']);
        $builder->add('file', FileUrlType::class, ['required' => false, 'label' => false]);

        if (!$this->authorizationChecker->isGranted($this->parameterBag->get('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', CaptchaType::class, ['required' => true, 'label' => 'Код']);
        }

        $builder->add('submit', SubmitType::class, ['label' => 'Отправить']);
    }

    /**
     * Уникальное имя формы.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'email_form';
    }
}
