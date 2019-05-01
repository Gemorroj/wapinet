<?php

namespace App\Controller\User;

use App\Entity\Event;
use App\Helper\Paginate;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventsController extends AbstractController
{
    public function indexAction(Request $request, Paginate $paginate): Response
    {
        $page = $request->get('page', 1);
        $user = $this->getUser();
        if (null === $user) {
            throw $this->createAccessDeniedException('Пользователь не найден');
        }

        /** @var EventRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Event::class);
        $events = $repository->findEventsQuery($user);

        $pagerfanta = $paginate->paginate($events, $page);

        return $this->render('User/Events/index.html.twig', [
            'user' => $user,
            'pagerfanta' => $pagerfanta,
        ]);
    }
}
