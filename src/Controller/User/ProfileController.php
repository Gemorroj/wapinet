<?php

namespace App\Controller\User;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class ProfileController extends Controller
{
    /**
     * Show custom user.
     *
     * @param string               $username
     * @param UserManagerInterface $userManager
     *
     * @throws AccessDeniedException|UsernameNotFoundException
     *
     * @return Response
     */
    public function showUserAction($username = null, UserManagerInterface $userManager)
    {
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        if (!\is_object($currentUser) || !$currentUser instanceof UserInterface) {
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
