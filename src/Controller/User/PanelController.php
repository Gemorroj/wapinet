<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Entity\UserPanel;
use App\Form\Type\User\PanelType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class PanelController extends AbstractController
{
    #[Route(path: '/panel/edit', name: 'wapinet_user_panel_edit')]
    public function editAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PanelType::class);
        $form->setData($user->getPanel());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    /** @var UserPanel $data */
                    $data = $form->getData();

                    $entityManager->persist($data);
                    $entityManager->flush();

                    $this->addFlash('success', 'Меню успешно обновлено');

                    return $this->redirectToRoute('wapinet_user_profile', ['username' => $user->getUsername()]);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('User/Panel/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
