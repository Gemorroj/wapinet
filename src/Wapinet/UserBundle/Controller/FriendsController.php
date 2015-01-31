<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\UserBundle\Entity\Friend;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Event\FriendEvent;


class FriendsController extends Controller
{
    /**
     * @param Request $request
     * @param string $username
     * @return Response
     */
    public function indexAction(Request $request, $username)
    {
        $page = $request->get('page', 1);
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        $friendRepository = $this->getDoctrine()->getRepository('Wapinet\UserBundle\Entity\Friend');
        $friends = $friendRepository->getFriendsQuery($user);
        $pagerfanta = $this->get('paginate')->paginate($friends, $page);

        return $this->render('WapinetUserBundle:Friends:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ));
    }


    /**
     * @param Request $request
     * @param string  $username
     *
     * @return RedirectResponse
     * @throws \LogicException|AccessDeniedException
     */
    public function addAction(Request $request, $username)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Вы не авторизованы');
        }

        $userManager = $this->get('fos_user.user_manager');
        $friend = $userManager->findUserByUsername($username);
        if (null === $friend) {
            throw $this->createNotFoundException('Пользователь не найден.');
        }

        $friendRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:Friend');
        $objFriend = $friendRepository->getFriend($user, $friend);

        if (null !== $objFriend) {
            throw new \LogicException($user->getUsername() . ' уже в друзьях.');
        }

        $objFriend = new Friend();
        $objFriend->setUser($user);
        $objFriend->setFriend($friend);

        $user->getFriends()->add($objFriend);
        $this->getDoctrine()->getManager()->merge($user);
        $this->getDoctrine()->getManager()->flush();

        $this->container->get('event_dispatcher')->dispatch(
            FriendEvent::FRIEND_ADD,
            new FriendEvent($user, $friend)
        );

        return $this->redirectToRoute('wapinet_user_profile', array('username' => $friend->getUsername()));
    }


    /**
     * @param Request $request
     * @param string  $username
     *
     * @return RedirectResponse
     * @throws \LogicException|AccessDeniedException
     */
    public function deleteAction(Request $request, $username)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Вы не авторизованы');
        }

        $userManager = $this->get('fos_user.user_manager');
        $friend = $userManager->findUserByUsername($username);
        if (null === $friend) {
            throw $this->createNotFoundException('Пользователь не найден.');
        }

        $friendRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:Friend');
        $objFriend = $friendRepository->getFriend($user, $friend);

        if (null === $objFriend) {
            throw new \LogicException($user->getUsername() . ' не в друзьях.');
        }

        $user->getFriends()->removeElement($objFriend);
        $this->getDoctrine()->getManager()->merge($user);
        $this->getDoctrine()->getManager()->flush();

        $this->container->get('event_dispatcher')->dispatch(
            FriendEvent::FRIEND_DELETE,
            new FriendEvent($user, $friend)
        );

        return $this->redirectToRoute('wapinet_user_profile', array('username' => $friend->getUsername()));
    }
}
