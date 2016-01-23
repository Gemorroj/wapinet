<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\UserBundle\Entity\Panel;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Form\Type\PanelType;

class PanelController extends Controller
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws AccessDeniedException
     */
    public function editAction(Request $request)
    {
        /** @var User $user */
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!\is_object($user) || !$user instanceof User) {
            throw $this->createAccessDeniedException('Вы должны быть авторизованы');
        }

        $form = $this->createForm(PanelType::class);
        $form->setData($user->getPanel());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    /** @var Panel $data */
                    $data = $form->getData();

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($data);
                    $em->flush();

                    $this->addFlash('success', 'Меню успешно обновлено');

                    return $this->redirectToRoute('fos_user_profile_show');
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetUserBundle:Panel:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
