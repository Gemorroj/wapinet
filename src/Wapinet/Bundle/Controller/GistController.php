<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\Bundle\Entity\Gist;
use Wapinet\Bundle\Event\GistEvent;
use Wapinet\Bundle\Form\Type\Gist\AddType;

class GistController extends Controller
{
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new AddType());
        $page = $request->get('page', 1);

        $gistManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $query = $gistManager->getListQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Gist:index.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ));
    }


    public function userAction(Request $request, $username)
    {
        $form = $this->createForm(new AddType());
        $page = $request->get('page', 1);

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            $this->createNotFoundException('Пользователь не найден');
        }

        $gistManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $query = $gistManager->getListQuery($user);
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Gist:index.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ));
    }


    /**
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws AccessDeniedException
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();
        if (null === $user) {
            throw new AccessDeniedException('Вы должны быть авторизованы для добавления сообщения');
        }

        $form = $this->createForm(new AddType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $gist = new Gist();
                    $gist->setBody($data['body']);
                    $gist->setSubject($data['subject']);

                    $gist->setUser($user);
                    $gist->setIp($request->getClientIp());
                    $gist->setBrowser($request->headers->get('User-Agent', ''));

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($gist);
                    $entityManager->flush();

                    $this->container->get('event_dispatcher')->dispatch(
                        GistEvent::GIST_ADD,
                        new GistEvent($data->getUser(), $gist)
                    );
                    $this->get('session')->getFlashBag()->add('notice', 'Сообщение успешно добавлено');
                }
            }
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('notice', $e->getMessage());
        }

        return $this->redirect($this->get('router')->generate('gist_index', array(), Router::ABSOLUTE_URL));
    }
}
