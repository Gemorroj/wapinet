<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class EventsController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $user = $this->getUser();
        if (null === $user) {
            $this->createNotFoundException('Пользователь не найден');
        }

        $repository = $this->getDoctrine()->getRepository('WapinetUserBundle:Subscriber');
        $events = $repository->findEventsQuery($user);

        $pagerfanta = $this->get('paginate')->paginate($events, $page);

        return $this->render('WapinetUserBundle:Events:index.html.twig', array(
            'user' => $user,
            'pagerfanta' => $pagerfanta,
        ));
    }
}