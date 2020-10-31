<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\Type\User\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * @Route("/user")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/profile/{username}", name="wapinet_user_profile", defaults={"username": null}, requirements={"username": ".+"})
     */
    public function showUserAction(?string $username = null): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Вы должны быть авторизованы');
        }

        if (null !== $username) {
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
            if (!$user instanceof User) {
                throw new UsernameNotFoundException('Пользователь не найден.');
            }
        } else {
            $user = $currentUser;
        }

        return $this->render('User/Profile/show.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/edit", name="wapinet_user_edit")
     */
    public function editUserAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            throw $this->createAccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('wapinet_user_profile', ['username' => $user->getUsername()]);
        }

        return $this->render('User/Profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
