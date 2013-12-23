<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class UsersController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);

        $userRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:User');
        $users = $userRepository->findBy(array(
            'enabled' => true,
            'locked' => false,
            'expired' => false,
        ),
        array(
            'lastActivity' => 'DESC',
            'username' => 'ASC',
        ));
        $pagerfanta = $this->get('paginate')->paginate($users, $page);

        return $this->render('WapinetUserBundle:Users:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }
}
