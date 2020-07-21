<?php

namespace App\Controller;

use App\Entity\Online;
use App\Entity\User;
use App\Form\Type\User\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DefaultController extends AbstractController
{
    public function checkAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

    public function loginAction(Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;

        // get the error if any (works with forward and redirect -- see below)
        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        $csrfToken = $csrfTokenManager ? $csrfTokenManager->getToken('authenticate')->getValue() : null;

        return $this->render('Security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
        ]);
    }

    public function registerAction(Request $request, EncoderFactoryInterface $encoderFactory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RegistrationType::class, new User());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            if ($entityManager->getRepository(User::class)->findOneBy(['username' => $user->getUsername()])) {
                $form->get('username')->addError(new FormError('Пользователь с таким username уже зарегистрирован'));
                goto render;
            }
            if ($entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])) {
                $form->get('email')->addError(new FormError('Пользователь с таким email уже зарегистрирован'));
                goto render;
            }

            $user->makeEncodedPassword($encoderFactory);

            $entityManager->persist($user);
            $entityManager->flush();

            $session = $request->getSession();
            if ($session) {
                $session->set(Security::LAST_USERNAME, $user->getUsername());
            }

            return $this->redirectToRoute('wapinet_login');
        }

        render:
        return $this->render('Security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function indexAction(): Response
    {
        return $this->render('Default/index.html.twig');
    }

    public function aboutAction(): Response
    {
        return $this->render('Default/about.html.twig');
    }

    public function onlineAction(): Response
    {
        return $this->render(
            'Default/online.html.twig',
            [
                'online' => $this->getDoctrine()->getRepository(Online::class)->findBy([], ['datetime' => 'DESC']),
            ]
        );
    }

    public function utilitiesAction(): Response
    {
        return $this->render('Default/utilities.html.twig');
    }

    public function programmingAction(): Response
    {
        return $this->render('Default/programming.html.twig');
    }

    public function openSourceAction(): Response
    {
        return $this->render('Default/open_source.html.twig');
    }

    public function gmanagerAction(): RedirectResponse
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    public function downloadsAction(): Response
    {
        return $this->render('Default/downloads.html.twig');
    }

    public function textbookAction(): Response
    {
        return $this->render('Default/textbook.html.twig');
    }

    public function videoCoursesAction(): Response
    {
        return $this->render('Default/video_courses.html.twig');
    }
}
