<?php

namespace App\Controller\User;

use App\Entity\Subscriber;
use App\Entity\User;
use App\Form\Type\User\SubscriberType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SubscriberController extends AbstractController
{
    /**
     * @throws AccessDeniedException
     *
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request)
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            throw $this->createAccessDeniedException('Вы должны быть авторизованы');
        }

        $form = $this->createForm(SubscriberType::class);
        $form->setData($user->getSubscriber());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    /** @var Subscriber $data */
                    $data = $form->getData();

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($data);
                    $em->flush();

                    $this->addFlash('success', 'Подписки успешно обновлены');

                    return $this->redirectToRoute('wapinet_user_profile', ['username' => $user->getUsername()]);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('User/Subscriber/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
