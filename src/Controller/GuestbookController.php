<?php

namespace App\Controller;

use App\Entity\Guestbook;
use App\Form\Type\Guestbook\MessageType;
use App\Repository\GuestbookRepository;
use App\Service\BotChecker;
use App\Service\Paginate;
use App\Service\StopSpam;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestbookController extends AbstractController
{
    public function indexAction(Request $request, Paginate $paginate): Response
    {
        $form = $this->createForm(MessageType::class);

        $page = $request->get('page', 1);
        /** @var GuestbookRepository $guestbookRepository */
        $guestbookRepository = $this->getDoctrine()->getRepository(Guestbook::class);
        $query = $guestbookRepository->getListQuery();
        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('Guestbook/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ]);
    }

    public function addAction(Request $request, BotChecker $botChecker, StopSpam $stopSpam): RedirectResponse
    {
        $form = $this->createForm(MessageType::class);
        $flashBag = $this->get('session')->getFlashBag();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $botChecker->checkRequest($request);
                    $stopSpam->checkRequest($request);

                    /** @var Guestbook $guestbook */
                    $guestbook = $form->getData();

                    $guestbook->setUser($this->getUser());
                    $guestbook->setIp($request->getClientIp());
                    $guestbook->setBrowser($request->headers->get('User-Agent', ''));

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($guestbook);
                    $entityManager->flush();

                    $flashBag->add('notice', 'Сообщение успешно добавлено');
                } else {
                    foreach ($form->getErrors(true) as $formError) {
                        $flashBag->add('notice', $formError->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $flashBag->add('notice', $e->getMessage());
        }

        return $this->redirectToRoute('guestbook_index');
    }
}
