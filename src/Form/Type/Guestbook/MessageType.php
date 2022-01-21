<?php

namespace App\Form\Type\Guestbook;

use App\Entity\Guestbook;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MessageType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private ParameterBagInterface $parameterBag;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ParameterBagInterface $parameterBag)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->parameterBag = $parameterBag;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if (!$this->authorizationChecker->isGranted($this->parameterBag->get('wapinet_role_nocaptcha'))) {
            $builder->add('captcha', CaptchaType::class, ['required' => true, 'label' => 'Код']);
        }
        $builder->add('message', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Сообщение',
                'maxlength' => 5000,
            ],
            'required' => true,
            'label' => false,
        ]);
        $builder->add('submit', SubmitType::class, ['label' => 'Написать']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Guestbook::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'message_form';
    }
}
