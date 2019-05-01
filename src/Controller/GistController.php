<?php

namespace App\Controller;

use App\Entity\Gist;
use App\Event\GistEvent;
use App\Form\Type\Gist\AddType;
use App\Form\Type\Gist\EditType;
use App\Form\Type\Gist\SearchType;
use App\Helper\BotChecker;
use App\Helper\Paginate;
use App\Helper\Sphinx;
use App\Repository\GistRepository;
use Exception;
use FOS\UserBundle\Model\UserManagerInterface;
use Pagerfanta\Pagerfanta;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use function uniqid;

class GistController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $form = $this->createForm(AddType::class);
        $page = $request->get('page', 1);

        /** @var GistRepository $gistRepository */
        $gistRepository = $this->getDoctrine()->getRepository(Gist::class);
        $query = $gistRepository->getListQuery();
        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('Gist/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ]);
    }

    /**
     * @param Request              $request
     * @param string               $username
     * @param UserManagerInterface $userManager
     *
     * @return Response
     */
    public function userAction(Request $request, string $username, UserManagerInterface $userManager): Response
    {
        $form = $this->createForm(AddType::class);
        $page = $request->get('page', 1);

        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        /** @var GistRepository $gistRepository */
        $gistRepository = $this->getDoctrine()->getRepository(Gist::class);
        $query = $gistRepository->getListQuery($user);
        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('Gist/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }

    /**
     * @param Request    $request
     * @param BotChecker $botChecker
     *
     * @throws AccessDeniedException
     *
     * @return RedirectResponse
     */
    public function addAction(Request $request, BotChecker $botChecker): RedirectResponse
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
                    $botChecker->checkRequest($request);

                    $gist = $form->getData();

                    $gist->setUser($user);
                    $gist->setIp($request->getClientIp());
                    $gist->setBrowser($request->headers->get('User-Agent', ''));

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($gist);
                    $entityManager->flush();

                    $this->get(EventDispatcherInterface::class)->dispatch(
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
        } catch (Exception $e) {
            $flashBag->add('notice', $e->getMessage());
        }

        return $this->redirectToRoute('gist_index');
    }

    public function viewAction(Gist $gist): Response
    {
        return $this->render('Gist/view.html.twig', [
            'gist' => $gist,
        ]);
    }

    /**
     * @param Gist $gist
     *
     * @throws AccessDeniedException|NotFoundHttpException
     *
     * @return RedirectResponse|JsonResponse
     */
    public function deleteAction(Gist $gist): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $gist);

        // БД
        $em = $this->getDoctrine()->getManager();
        $em->remove($gist);
        $em->flush();

        // переадресация на главную
        $url = $this->generateUrl('gist_index', [], Router::ABSOLUTE_URL);

        return $this->redirect($url);
    }

    /**
     * @param Request          $request
     * @param SessionInterface $session
     * @param string|null      $key
     *
     * @return Response|RedirectResponse
     */
    public function searchAction(Request $request, SessionInterface $session, $key = null): Response
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = uniqid('', false);
                    $session->set('gist_search', [
                        'key' => $key,
                        'data' => $data,
                    ]);
                }

                return $this->redirectToRoute('gist_search', ['key' => $key]);
            }

            if (null !== $key && true === $session->has('gist_search')) {
                $search = $session->get('gist_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchSphinx($search['data'], $page);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Gist/search.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ]);
    }

    /**
     * @param array $data
     * @param int   $page
     *
     * @throws RuntimeException
     *
     * @return Pagerfanta
     */
    private function searchSphinx(array $data, int $page = 1): Pagerfanta
    {
        /** @var Sphinx $client */
        $client = $this->get(Sphinx::class);
        $sphinxQl = $client->select($page)
            ->from('gist')
            ->match(['subject', 'body'], $data['search'])
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
     * @param Gist    $gist
     *
     * @throws AccessDeniedException|NotFoundHttpException
     *
     * @return RedirectResponse|JsonResponse|Response
     */
    public function editAction(Request $request, Gist $gist): Response
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

                    $url = $this->generateUrl('gist_view', ['id' => $gist->getId()], Router::ABSOLUTE_URL);

                    return $this->redirect($url);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Gist/edit.html.twig', [
            'form' => $form->createView(),
            'gist' => $gist,
        ]);
    }

    private function editGistData(Request $request, Gist $data, Gist $newData): Gist
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

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Sphinx::class] = '?'.Sphinx::class;
        $services[EventDispatcherInterface::class] = '?'.EventDispatcherInterface::class;
        $services[Paginate::class] = Paginate::class;

        return $services;
    }
}
