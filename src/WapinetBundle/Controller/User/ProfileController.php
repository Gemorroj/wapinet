<?php
namespace WapinetBundle\Controller\User;

use FOS\UserBundle\Controller\ProfileController as BaseController;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;


class ProfileController extends BaseController
{
    /**
     * Show custom user
     * @param string $username
     * @return Response
     * @throws AccessDeniedException|UsernameNotFoundException
     */
    public function showUserAction($username = null)
    {
        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        if (!\is_object($currentUser) || !$currentUser instanceof UserInterface) {
            throw $this->createAccessDeniedException('Вы должны быть авторизованы');
        }

        if (null !== $username) {
            $userManager = $this->get('fos_user.user_manager');
            $user = $userManager->findUserByUsername($username);
            if (!$user instanceof UserInterface) {
                throw new UsernameNotFoundException('Пользователь не найден.');
            }
        } else {
            $user = $currentUser;
        }

        return $this->render('@FOSUser/Profile/show.html.twig', array('user' => $user));
    }
}
