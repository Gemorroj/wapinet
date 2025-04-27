<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Service\CaptchaService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class CaptchaType extends AbstractType
{
    private SessionInterface $session;
    private RouterInterface $router;

    public function __construct(RequestStack $requestStack, RouterInterface $router)
    {
        $this->session = $requestStack->getSession();
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $code = $form->getData();
            $expectedPhrase = $this->session->get(CaptchaService::SESSION_KEY);

            if (!$code || !$expectedPhrase || \strtr(\strtolower($code), 'oil', '01l') !== \strtr(\strtolower($expectedPhrase), 'oil', '01l')) {
                $form->addError(new FormError('Неверная капча'));
            }

            $this->session->remove(CaptchaService::SESSION_KEY);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars = \array_replace($view->vars, [
            'captcha_url' => $this->router->generate('app.captcha', ['n' => \microtime(true)]),
            'captcha_id' => \str_replace('.', '-', \uniqid('_captcha_', true)),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'captcha';
    }
}
