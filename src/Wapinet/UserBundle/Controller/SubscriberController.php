<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Form\Type\SubscriberType;

class SubscriberController extends Controller
{
    public function editAction(Request $request)
    {
        /** @var User $user */
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof User) {
            throw new AccessDeniedException('Вы должны быть авторизованы.');
        }

        $form = $this->createForm(new SubscriberType());
        $form->setData($user->getPanel());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $em = $this->getDoctrine()->getManager();
                    //$em->persist($data);
                    //$em->flush();
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
