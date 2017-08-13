<?php
namespace WapinetUserBundle\Controller;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Controller\ProfileController as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use WapinetUserBundle\Entity\User;


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

        return $this->render('WapinetUserBundle:Profile:show.html.twig', array('user' => $user));
    }
}
