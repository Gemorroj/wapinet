<?php
namespace WapinetBundle\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class EventsController extends Controller
{
    /**
     * @param Request $request
     * @throws AccessDeniedException
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Пользователь не найден');
        }

        $repository = $this->getDoctrine()->getRepository('WapinetBundle:Event');
        $events = $repository->findEventsQuery($user);

        $pagerfanta = $this->get('paginate')->paginate($events, $page);

        return $this->render('WapinetBundle:User/Events:index.html.twig', array(
            'user' => $user,
            'pagerfanta' => $pagerfanta,
        ));
    }
}
