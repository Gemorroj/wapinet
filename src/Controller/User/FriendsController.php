<?php

namespace App\Controller\User;

use App\Entity\Friend;
use App\Entity\User;
use App\Event\FriendEvent;
use App\Repository\FriendRepository;
use App\Service\Paginate;
use FOS\UserBundle\Model\UserManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FriendsController extends AbstractController
{
    public function indexAction(Request $request, string $username, UserManagerInterface $userManager, Paginate $paginate): Response
    {
        $page = $request->get('page', 1);

        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->getDoctrine()->getRepository(Friend::class);
        $friends = $friendRepository->getFriendsQuery($user);
        $pagerfanta = $paginate->paginate($friends, $page);

        return $this->render('User/Friends/index.html.twig', [
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }

    public function addAction(string $username, UserManagerInterface $userManager, EventDispatcherInterface $eventDispatcher): RedirectResponse
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

        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->getDoctrine()->getRepository(Friend::class);
        $objFriend = $friendRepository->getFriend($user, $friend);

        if (null !== $objFriend) {
            throw new LogicException($user->getUsername().' уже в друзьях.');
        }

        $objFriend = new Friend();
        $objFriend->setUser($user);
        $objFriend->setFriend($friend);

        $user->getFriends()->add($objFriend);
        $this->getDoctrine()->getManager()->merge($user);
        $this->getDoctrine()->getManager()->flush();

        $eventDispatcher->dispatch(
            FriendEvent::FRIEND_ADD,
            new FriendEvent($user, $friend)
        );

        return $this->redirectToRoute('wapinet_user_profile', ['username' => $friend->getUsername()]);
    }

    public function deleteAction(string $username, UserManagerInterface $userManager, EventDispatcherInterface $eventDispatcher): RedirectResponse
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

        /** @var FriendRepository $friendRepository */
        $friendRepository = $this->getDoctrine()->getRepository(Friend::class);
        $objFriend = $friendRepository->getFriend($user, $friend);

        if (null === $objFriend) {
            throw new LogicException($user->getUsername().' не в друзьях.');
        }

        $user->getFriends()->removeElement($objFriend);
        $this->getDoctrine()->getManager()->merge($user);
        $this->getDoctrine()->getManager()->flush();

        $eventDispatcher->dispatch(
            FriendEvent::FRIEND_DELETE,
            new FriendEvent($user, $friend)
        );

        return $this->redirectToRoute('wapinet_user_profile', ['username' => $friend->getUsername()]);
    }
}
