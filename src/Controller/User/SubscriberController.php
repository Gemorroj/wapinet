<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\UserSubscriber;
use App\Form\Type\User\SubscriberType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class SubscriberController extends AbstractController
{
    #[Route(path: '/subscriber/edit', name: 'wapinet_user_subscriber_edit')]
    public function editAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $subscriber = $user->getSubscriber() ?: new UserSubscriber();
        $form = $this->createForm(SubscriberType::class, $subscriber);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    /** @var UserSubscriber $data */
                    $data = $form->getData();

                    $entityManager->persist($data);
                    $entityManager->flush();

                    $this->addFlash('success', 'Подписки успешно обновлены');

                    return $this->redirectToRoute('wapinet_user_profile', ['username' => $user->getUsername()]);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('User/Subscriber/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
