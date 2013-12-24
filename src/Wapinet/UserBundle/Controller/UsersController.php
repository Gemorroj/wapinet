<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\UserBundle\Form\Type\SearchType;


class UsersController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(new SearchType());

        $userRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:User');
        $users = $userRepository->getOnlineUsersQuery();
        $pagerfanta = $this->get('paginate')->paginate($users, $page);

        return $this->render('WapinetUserBundle:Users:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'form' => $form->createView(),
        ));
    }
}
