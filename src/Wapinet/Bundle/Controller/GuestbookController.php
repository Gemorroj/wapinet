<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Entity\Guestbook;
use Wapinet\Bundle\Form\Type\Guestbook\MessageType;
use Symfony\Component\Routing\Router;

class GuestbookController extends Controller
{
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new MessageType());

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
        $form = $this->createForm(new MessageType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $guestbook = new Guestbook();
                    $guestbook->setMessage($data['message']);

                    $guestbook->setUser($this->getUser());
                    $guestbook->setIp($request->getClientIp());
                    $guestbook->setBrowser($request->headers->get('User-Agent', ''));

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($guestbook);
                    $entityManager->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Сообщение успешно добавлено');
                }
            }
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('notice', $e->getMessage());
        }

        return $this->redirect($this->get('router')->generate('guestbook_index', array(), Router::ABSOLUTE_URL));
    }
}
