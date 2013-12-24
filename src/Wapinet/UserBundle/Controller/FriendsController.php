<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\UserBundle\Entity\Friend;
use Wapinet\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
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
            $this->createNotFoundException('Пользователь не найден');
        }

        $friendRepository = $this->getDoctrine()->getRepository('Wapinet\UserBundle\Entity\Friend');
        $friends = $friendRepository->findBy(array('user' => $user));
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
     * @return JsonResponse|RedirectResponse
     * @throws \LogicException|AccessDeniedException
     */
    public function addAction(Request $request, $username)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $user) {
            throw new AccessDeniedException('Вы не авторизованы.');
        }

        $userManager = $this->get('fos_user.user_manager');
        $friend = $userManager->findUserByUsername($username);
        if (null === $friend) {
            $this->createNotFoundException('Пользователь не найден.');
        }

        $friendRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:Friend');
        $isFriend = $friendRepository->findOneBy(array(
            'user' => $user,
            'friend' => $friend,
        ));

        if (null !== $isFriend) {
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

        $router = $this->get('router');
        $url = $router->generate('wapinet_user_profile', array('username' => $friend->getUsername()), Router::ABSOLUTE_URL);

        if (true === $request->isXmlHttpRequest()) {
            return new JsonResponse(array('url' => $url));
        }

        return new RedirectResponse($url);
    }


    /**
     * @param Request $request
     * @param string  $username
     *
     * @return JsonResponse|RedirectResponse
     * @throws \LogicException|AccessDeniedException
     */
    public function deleteAction(Request $request, $username)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (null === $user) {
            throw new AccessDeniedException('Вы не авторизованы.');
        }

        $userManager = $this->get('fos_user.user_manager');
        $friend = $userManager->findUserByUsername($username);
        if (null === $friend) {
            $this->createNotFoundException('Пользователь не найден.');
        }

        $friendRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:Friend');
        $objFriend = $friendRepository->findOneBy(array(
            'user' => $user,
            'friend' => $friend,
        ));

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

        $router = $this->get('router');
        $url = $router->generate('wapinet_user_profile', array('username' => $friend->getUsername()), Router::ABSOLUTE_URL);

        if (true === $request->isXmlHttpRequest()) {
            return new JsonResponse(array('url' => $url));
        }

        return new RedirectResponse($url);
    }
}
