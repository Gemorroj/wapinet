<?php

namespace App\Controller\User;

use App\Entity\Friend;
use App\Entity\User;
use App\Event\FriendEvent;
use App\Repository\FriendRepository;
use App\Repository\UserRepository;
use App\Service\Paginate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class FriendsController extends AbstractController
{
    /**
     * @Route("/friends/list/{username}", name="wapinet_user_friends", requirements={"username": ".+"})
     */
    public function indexAction(Request $request, string $username, Paginate $paginate, UserRepository $userRepository, FriendRepository $friendRepository): Response
    {
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        $page = $request->get('page', 1);

        $friends = $friendRepository->getFriendsQuery($user);
        $pagerfanta = $paginate->paginate($friends, $page);

        return $this->render('User/Friends/index.html.twig', [
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/friends/add/{username}", name="wapinet_user_friends_add", requirements={"username": ".+"})
     */
    public function addAction(string $username, EventDispatcherInterface $eventDispatcher, UserRepository $userRepository, FriendRepository $friendRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        /** @var User|null $friend */
        $friend = $userRepository->findOneBy(['username' => $username]);
        if (!$friend) {
            throw $this->createNotFoundException('Пользователь не найден.');
        }

        $objFriend = $friendRepository->getFriend($user, $friend);
        if (null !== $objFriend) {
            throw new \LogicException($user->getUsername().' уже в друзьях.');
        }

        $objFriend = new Friend();
        $objFriend->setUser($user);
        $objFriend->setFriend($friend);

        $user->getFriends()->add($objFriend);
        $entityManager->persist($user);
        $entityManager->flush();

        $eventDispatcher->dispatch(
            new FriendEvent($user, $friend),
            FriendEvent::FRIEND_ADD
        );

        return $this->redirectToRoute('wapinet_user_profile', ['username' => $friend->getUsername()]);
    }

    /**
     * @Route("/friends/delete/{username}", name="wapinet_user_friends_delete", requirements={"username": ".+"})
     */
    public function deleteAction(string $username, EventDispatcherInterface $eventDispatcher, UserRepository $userRepository, FriendRepository $friendRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        /** @var User|null $friend */
        $friend = $userRepository->findOneBy(['username' => $username]);
        if (!$friend) {
            throw $this->createNotFoundException('Пользователь не найден.');
        }

        $objFriend = $friendRepository->getFriend($user, $friend);
        if (null === $objFriend) {
            throw new \LogicException($user->getUsername().' не в друзьях.');
        }

        $user->getFriends()->removeElement($objFriend);
        $entityManager->persist($user);
        $entityManager->flush();

        $eventDispatcher->dispatch(
            new FriendEvent($user, $friend),
            FriendEvent::FRIEND_DELETE
        );

        return $this->redirectToRoute('wapinet_user_profile', ['username' => $friend->getUsername()]);
    }
}
