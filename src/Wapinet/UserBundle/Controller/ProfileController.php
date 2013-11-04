<?php
namespace Wapinet\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Controller\ProfileController as BaseController;
use Symfony\Component\HttpFoundation\Response;
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
        $currentUser = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($currentUser) || !$currentUser instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }
        if (null !== $username) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserByUsername($username);
            if (!$user instanceof UserInterface) {
                throw new UsernameNotFoundException('Пользователь не найден.');
            }
        } else {
            $user = $currentUser;
        }

        return $this->container->get('templating')->renderResponse('FOSUserBundle:Profile:show.html.'.$this->container->getParameter('fos_user.template.engine'), array('user' => $user));
    }
}
