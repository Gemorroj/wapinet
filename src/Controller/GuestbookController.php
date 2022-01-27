<?php

namespace App\Controller;

use App\Entity\Guestbook;
use App\Form\Type\Guestbook\MessageType;
use App\Repository\GuestbookRepository;
use App\Service\BotChecker;
use App\Service\Paginate;
use App\Service\StopSpam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/guestbook')]
class GuestbookController extends AbstractController
{
    #[Route(path: '', name: 'guestbook_index', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function indexAction(Request $request, Paginate $paginate, GuestbookRepository $guestbookRepository): Response
    {
        $form = $this->createForm(MessageType::class);

        $page = $request->get('page', 1);

        $query = $guestbookRepository->getListQuery();
        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('Guestbook/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route(path: '', name: 'guestbook_add', methods: ['POST'])]
    public function addAction(Request $request, BotChecker $botChecker, StopSpam $stopSpam, EntityManagerInterface $entityManager): RedirectResponse
    {
        $form = $this->createForm(MessageType::class);

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

                    $entityManager->persist($guestbook);
                    $entityManager->flush();

                    $this->addFlash('notice', 'Сообщение успешно добавлено');
                } else {
                    foreach ($form->getErrors(true) as $formError) {
                        $this->addFlash('notice', $formError->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('notice', $e->getMessage());
        }

        return $this->redirectToRoute('guestbook_index');
    }
}
