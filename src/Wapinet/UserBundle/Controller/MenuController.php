<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\UserBundle\Entity\Menu;
use Wapinet\UserBundle\Entity\User;
use Wapinet\UserBundle\Form\Type\MenuType;

class MenuController extends Controller
{
    public function editAction(Request $request)
    {
        $token = $this->container->get('security.context')->getToken();
        if (null === $token) {
            throw new AccessDeniedException('Вы должны быть авторизованы.');
        }
        /** @var User $user */
        $user = $token->getUser();

        if (!is_object($user) || !$user instanceof User) {
            throw new AccessDeniedException('Вы должны быть авторизованы.');
        }

        $form = $this->createForm(new MenuType());
        $form->setData($user->getMenu());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    /** @var Menu $data */
                    $data = $form->getData();

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($data);
                    $em->flush();
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetUserBundle:Menu:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
