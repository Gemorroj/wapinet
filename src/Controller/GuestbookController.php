<?php

namespace App\Controller;

use App\Entity\Guestbook;
use App\Form\Type\Guestbook\MessageType;
use App\Helper\Paginate;
use App\Repository\GuestbookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestbookController extends AbstractController
{
    /**
     * @param Request  $request
     * @param Paginate $paginate
     *
     * @return Response
     */
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

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function addAction(Request $request): RedirectResponse
    {
        $form = $this->createForm(MessageType::class);
        $flashBag = $this->get('session')->getFlashBag();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->get('bot_checker')->checkRequest($request);
                    $this->get('stop_spam')->checkRequest($request);

                    /** @var Guestbook $data */
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
