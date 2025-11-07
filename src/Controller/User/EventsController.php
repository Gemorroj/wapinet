<?php

namespace App\Controller\User;

use App\Repository\EventRepository;
use App\Service\Paginate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class EventsController extends AbstractController
{
    #[Route(path: '/events', name: 'wapinet_user_events')]
    public function indexAction(Request $request, Paginate $paginate, EventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        $page = (int) $request->query->get('page', 1);

        $events = $eventRepository->findEventsQuery($user);

        $pagerfanta = $paginate->paginate($events, $page);

        return $this->render('User/Events/index.html.twig', [
            'user' => $user,
            'pagerfanta' => $pagerfanta,
        ]);
    }
}
