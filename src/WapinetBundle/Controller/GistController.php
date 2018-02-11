<?php

namespace WapinetBundle\Controller;

use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use WapinetBundle\Entity\Gist;
use WapinetBundle\Event\GistEvent;
use WapinetBundle\Form\Type\Gist\AddType;
use WapinetBundle\Form\Type\Gist\EditType;
use WapinetBundle\Form\Type\Gist\SearchType;

class GistController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(AddType::class);
        $page = $request->get('page', 1);

        $gistManager = $this->getDoctrine()->getRepository('WapinetBundle:Gist');
        $query = $gistManager->getListQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Gist:index.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ));
    }


    /**
     * @param Request $request
     * @param string $username
     * @return Response
     */
    public function userAction(Request $request, $username)
    {
        $form = $this->createForm(AddType::class);
        $page = $request->get('page', 1);

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        $gistManager = $this->getDoctrine()->getRepository('WapinetBundle:Gist');
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
            throw $this->createAccessDeniedException('Вы должны быть авторизованы для добавления сообщения');
        }

        $form = $this->createForm(AddType::class);
        $flashBag = $this->get('session')->getFlashBag();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->get('bot_checker')->checkRequest($request);

                    $gist = $form->getData();

                    $gist->setUser($user);
                    $gist->setIp($request->getClientIp());
                    $gist->setBrowser($request->headers->get('User-Agent', ''));

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($gist);
                    $entityManager->flush();

                    $this->get('event_dispatcher')->dispatch(
                        GistEvent::GIST_ADD,
                        new GistEvent($user, $gist)
                    );
                    $flashBag->add('notice', 'Сообщение успешно добавлено');
                } else {
                    foreach ($form->getErrors(true) as $formError) {
                        $flashBag->add('notice', $formError->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $flashBag->add('notice', $e->getMessage());
        }

        return $this->redirectToRoute('gist_index');
    }


    /**
     * @param Gist $gist
     * @return Response
     */
    public function viewAction(Gist $gist)
    {
        return $this->render('WapinetBundle:Gist:view.html.twig', array(
            'gist' => $gist,
        ));
    }


    /**
     * @param Request $request
     * @param Gist $gist
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|JsonResponse
     */
    public function deleteAction(Request $request, Gist $gist)
    {
        $this->denyAccessUnlessGranted('DELETE', $gist);

        // БД
        $em = $this->getDoctrine()->getManager();
        $em->remove($gist);
        $em->flush();

        // переадресация на главную
        $url = $this->generateUrl('gist_index', array(), Router::ABSOLUTE_URL);

        return $this->redirect($url);
    }


    /**
     * @param Request $request
     * @param string|null $key
     * @return Response|RedirectResponse
     */
    public function searchAction(Request $request, $key = null)
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;
        $session = $this->get('session');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid('', false);
                    $session->set('gist_search', array(
                        'key' => $key,
                        'data' => $data
                    ));
                }

                return $this->redirectToRoute('gist_search', array('key' => $key));
            }

            if (null !== $key && true === $session->has('gist_search')) {
                $search = $session->get('gist_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchSphinx($search['data'], $page);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Gist:search.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ));
    }


    /**
     * @param array $data
     * @param int   $page
     *
     * @throws \RuntimeException
     * @return Pagerfanta
     */
    protected function searchSphinx(array $data, $page = 1)
    {
        $client = $this->get('sphinx');
        $sphinxQl = $client->select($page)
            ->from('gist')
            ->match(array('subject', 'body'), $data['search'])
        ;

        if ('date' === $data['sort']) {
            $sphinxQl->orderBy('created_at_ts', 'desc');
        } else {
            $sphinxQl->orderBy('WEIGHT()', 'desc');
        }

        return $client->getPagerfanta($sphinxQl, Gist::class);
    }


    /**
     * @param Request $request
     * @param Gist $gist
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|JsonResponse|Response
     */
    public function editAction(Request $request, Gist $gist)
    {
        $this->denyAccessUnlessGranted('EDIT', $gist);

        $form = $this->createForm(EditType::class);
        $form['body']->setData($gist->getBody());
        $form['subject']->setData($gist->getSubject());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $newGist = $form->getData();
                    $this->editGistData($request, $gist, $newGist);

                    $url = $this->generateUrl('gist_view', array('id' => $gist->getId()), Router::ABSOLUTE_URL);

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Gist:edit.html.twig', array(
            'form' => $form->createView(),
            'gist' => $gist,
        ));
    }


    /**
     * @param Request $request
     * @param Gist    $data
     * @param Gist   $newData
     * @return Gist
     */
    protected function editGistData(Request $request, Gist $data, Gist $newData)
    {
        $data->setSubject($newData->getSubject());
        $data->setBody($newData->getBody());

        // обновляем ip и браузер только если сообщение редактирует владелец
        if ($data->getUser()->getId() === $this->getUser()->getId()) {
            //$data->setUser($this->getUser());
            $data->setIp($request->getClientIp());
            $data->setBrowser($request->headers->get('User-Agent', ''));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($data);
        $entityManager->flush();

        return $data;
    }
}
