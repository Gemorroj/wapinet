<?php

namespace App\Controller\User;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class ProfileController extends AbstractController
{
    /**
     * Show custom user.
     *
     * @throws AccessDeniedException|UsernameNotFoundException
     *
     * @return Response
     */
    public function showUserAction(?string $username = null, UserManagerInterface $userManager)
    {
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser instanceof UserInterface) {
            throw $this->createAccessDeniedException('Вы должны быть авторизованы');
        }

        if (null !== $username) {
            $user = $userManager->findUserByUsername($username);
            if (!$user instanceof UserInterface) {
                throw new UsernameNotFoundException('Пользователь не найден.');
            }
        } else {
            $user = $currentUser;
        }

        return $this->render('@FOSUser/Profile/show.html.twig', ['user' => $user]);
    }
}
