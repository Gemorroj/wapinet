<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\Type\User\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[Route('/user')]
class ProfileController extends AbstractController
{
    #[Route(path: '/profile/{username}', name: 'wapinet_user_profile', requirements: ['username' => '.+'], defaults: ['username' => null])]
    public function showUserAction(UserRepository $userRepository, string $username = null): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException();
        }

        if (null !== $username) {
            $user = $userRepository->findOneBy(['username' => $username]);
            if (!$user instanceof User) {
                $e = new UserNotFoundException('Пользователь "'.$username.'" не найден.');
                $e->setUserIdentifier($username);
                throw $e;
            }
        } else {
            $user = $currentUser;
        }

        return $this->render('User/Profile/show.html.twig', ['user' => $user]);
    }

    #[Route(path: '/edit', name: 'wapinet_user_edit')]
    public function editUserAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
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
