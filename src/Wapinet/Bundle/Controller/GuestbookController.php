<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Entity\Guestbook;
use Wapinet\Bundle\Form\Type\Guestbook\MessageType;

class GuestbookController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(MessageType::class);

        $page = $request->get('page', 1);
        $guestbookManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Guestbook');
        $query = $guestbookManager->getListQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Guestbook:index.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ));
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function addAction(Request $request)
    {
        $form = $this->createForm(MessageType::class);
        $flashBag = $this->get('session')->getFlashBag();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->get('bot_checker')->checkRequest($request);

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
