<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


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
}
