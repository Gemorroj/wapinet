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
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof User) {
            throw new AccessDeniedException('Вы должны быть авторизованы.');
        }

        $form = $this->createForm(new PanelType());
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

                    $this->get('session')->getFlashBag()->add('success', 'Меню успешно обновлено');
                    $url = $this->container->get('router')->generate('fos_user_profile_show');
                    return new RedirectResponse($url);
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
