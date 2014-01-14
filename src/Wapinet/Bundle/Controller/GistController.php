<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class GistController extends Controller
{
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $gistManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $query = $gistManager->getListQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Gist:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }


    public function userAction(Request $request, $username)
    {
        $page = $request->get('page', 1);

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            $this->createNotFoundException('Пользователь не найден');
        }

        $gistManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $query = $gistManager->getListQuery($user);
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Gist:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ));
    }
}
