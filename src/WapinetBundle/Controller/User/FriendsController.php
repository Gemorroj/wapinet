<?php
namespace WapinetBundle\Controller\User;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use WapinetBundle\Entity\Friend;
use WapinetBundle\Entity\User;
use WapinetBundle\Event\FriendEvent;


class FriendsController extends Controller
{
    /**
     * @param Request $request
     * @param string $username
     * @param UserManagerInterface $userManager
     * @return Response
     */
    public function indexAction(Request $request, $username, UserManagerInterface $userManager)
    {
        $page = $request->get('page', 1);

        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        $friendRepository = $this->getDoctrine()->getRepository(Friend::class);
        $friends = $friendRepository->getFriendsQuery($user);
        $pagerfanta = $this->get('paginate')->paginate($friends, $page);

        return $this->render('@Wapinet/User/Friends/index.html.twig', [
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }


    /**
     * @param string  $username
     * @param UserManagerInterface $userManager
     *
     * @return RedirectResponse
     * @throws \LogicException|AccessDeniedException
     */
    public function addAction($username, UserManagerInterface $userManager)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Вы не авторизованы');
        }

        $friend = $userManager->findUserByUsername($username);
        if (null === $friend) {
            throw $this->createNotFoundException('Пользователь не найден.');
        }

        $friendRepository = $this->getDoctrine()->getRepository(Friend::class);
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

        return $this->redirectToRoute('wapinet_user_profile', ['username' => $friend->getUsername()]);
    }


    /**
     * @param string  $username
     * @param UserManagerInterface $userManager
     *
     * @return RedirectResponse
     * @throws \LogicException|AccessDeniedException
     */
    public function deleteAction($username, UserManagerInterface $userManager)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Вы не авторизованы');
        }

        $friend = $userManager->findUserByUsername($username);
        if (null === $friend) {
            throw $this->createNotFoundException('Пользователь не найден.');
        }

        $friendRepository = $this->getDoctrine()->getRepository(Friend::class);
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

        return $this->redirectToRoute('wapinet_user_profile', ['username' => $friend->getUsername()]);
    }
}
