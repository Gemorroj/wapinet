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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;

class EmailType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ParameterBagInterface $parameterBag;
    private RequestStack $requestStack;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ParameterBagInterface $parameterBag, RequestStack $requestStack)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $request = $this->requestStack->getCurrentRequest();
        $fromMessage = 'Укажите username без @. Адрес отправки будет вида username@'.($request ? $request->getHost() : 'localhost');

        $builder->add('to', CoreEmailType::class, [
            'label' => 'Кому',
            'data' => '@',
            'attr' => [
                'placeholder' => 'email@example.com',
            ],
            'constraints' => [
                new Email(['mode' => Email::VALIDATION_MODE_HTML5]),
            ],
        ]);
        $builder->add('from', TextType::class, [
            'label' => 'От кого',
            'attr' => [
                'placeholder' => 'username',
                'pattern' => '^(?!.*@).*',
                'title' => $fromMessage,
            ],
            'constraints' => [
                new Regex('/^(?!.*@).*/', $fromMessage),
            ],
        ]);
        $builder->add('subject', TextType::class, [
            'label' => 'Тема',
        ]);
        $builder->add('message', TextareaType::class, [
            'label' => 'Сообщение',
        ]);
        $builder->add('file', FileUrlType::class, [
            'required' => false,
            'label' => false,
        ]);

        if (!$this->authorizationChecker->isGranted($this->parameterBag->get('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', CaptchaType::class, [
                'required' => true,
                'label' => 'Код',
            ]);
        }

        $builder->add('submit', SubmitType::class, ['label' => 'Отправить']);
    }

    public function getBlockPrefix(): string
    {
        return 'email_form';
    }
}
