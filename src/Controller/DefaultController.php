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
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class DefaultController extends AbstractController
{
    /**
     * @Route("/login_check", methods={"POST"}, name="wapinet_check")
     */
    public function checkAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * @Route("/logout", name="wapinet_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

    /**
     * @Route("/login", name="wapinet_login")
     */
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

    /**
     * @Route("/registration", name="wapinet_register")
     */
    public function registerAction(Request $request, PasswordHasherFactoryInterface $passwordHasherFactory, EntityManagerInterface $entityManager): Response
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

            $user->makeEncodedPassword($passwordHasherFactory);

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

    /**
     * @Route("", name="index")
     */
    public function indexAction(): Response
    {
        return $this->render('Default/index.html.twig');
    }

    /**
     * @Route("/about", name="about")
     */
    public function aboutAction(): Response
    {
        return $this->render('Default/about.html.twig');
    }

    /**
     * @Route("/online", name="online")
     */
    public function onlineAction(): Response
    {
        return $this->render(
            'Default/online.html.twig',
            [
                'online' => $this->getDoctrine()->getRepository(Online::class)->findBy([], ['datetime' => 'DESC']),
            ]
        );
    }

    /**
     * @Route("/utilities", name="utilities")
     */
    public function utilitiesAction(): Response
    {
        return $this->render('Default/utilities.html.twig');
    }

    /**
     * @Route("/programming", name="programming")
     */
    public function programmingAction(): Response
    {
        return $this->render('Default/programming.html.twig');
    }

    /**
     * @Route("/open_source", name="open_source")
     */
    public function openSourceAction(): Response
    {
        return $this->render('Default/open_source.html.twig');
    }

    /**
     * @Route("/gmanager", name="gmanager")
     */
    public function gmanagerAction(): RedirectResponse
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    /**
     * @Route("/downloads", name="downloads")
     */
    public function downloadsAction(): Response
    {
        return $this->render('Default/downloads.html.twig');
    }

    /**
     * @Route("/textbook", name="textbook")
     */
    public function textbookAction(): Response
    {
        return $this->render('Default/textbook.html.twig');
    }

    /**
     * @Route("/video_courses", name="video_courses")
     */
    public function videoCoursesAction(): Response
    {
        return $this->render('Default/video_courses.html.twig');
    }
}
