<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class CommentsController extends Controller
{
    /**
     * @param string $username
     * @param int $page
     * @return Response
     */
    public function indexAction($username, $page = 1)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            $this->createNotFoundException('Пользователь не найден');
        }

        $commentManager = $this->get('wapinet_comment.manager.comment');
        $pagerfanta = $commentManager->findCommentsByUser($user, null, $page);

        return $this->render('WapinetUserBundle:Comments:index.html.twig', array(
            'comments' => $pagerfanta,
            'user' => $user,
        ));
    }
}
