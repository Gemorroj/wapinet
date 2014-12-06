<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\UserBundle\Entity\Subscriber;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Form\Type\SubscriberType;

class SubscriberController extends Controller
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
            $this->createAccessDeniedException('Вы должны быть авторизованы');
        }

        $form = $this->createForm(new SubscriberType());
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

                    return $this->redirectToRoute('fos_user_profile_show');
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetUserBundle:Subscriber:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
