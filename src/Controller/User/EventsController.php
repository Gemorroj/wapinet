<?php

namespace App\Controller\User;

use App\Repository\EventRepository;
use App\Service\Paginate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class EventsController extends AbstractController
{
    /**
     * @Route("/events", name="wapinet_user_events")
     */
    public function indexAction(Request $request, Paginate $paginate, EventRepository $eventRepository): Response
    {
        $page = $request->get('page', 1);
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Пользователь не найден');
        }

        $events = $eventRepository->findEventsQuery($user);

        $pagerfanta = $paginate->paginate($events, $page);

        return $this->render('User/Events/index.html.twig', [
            'user' => $user,
            'pagerfanta' => $pagerfanta,
        ]);
    }
}
