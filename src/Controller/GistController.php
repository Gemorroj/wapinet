<?php

namespace App\Controller;

use App\Entity\Gist;
use App\Entity\User;
use App\Form\Type\Gist\AddType;
use App\Form\Type\Gist\EditType;
use App\Form\Type\Gist\SearchType;
use App\Message\GistAddMessage;
use App\Repository\GistRepository;
use App\Repository\UserRepository;
use App\Service\BotChecker;
use App\Service\Manticore;
use App\Service\Paginate;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Router;

#[Route('/gist')]
class GistController extends AbstractController
{
    #[Route(path: '', name: 'gist_index', options: ['expose' => true], methods: ['GET', 'HEAD'])]
    public function indexAction(Request $request, GistRepository $gistRepository, Paginate $paginate): Response
    {
        $form = $this->createForm(AddType::class);
        $page = (int) $request->query->get('page', 1);

        $query = $gistRepository->getListQuery();
        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('Gist/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route(path: '/users/{username}', name: 'gist_user', requirements: ['username' => '.+'])]
    public function userAction(Request $request, string $username, UserRepository $userRepository, GistRepository $gistRepository, Paginate $paginate): Response
    {
        $form = $this->createForm(AddType::class);
        $page = (int) $request->query->get('page', 1);

        /** @var User|null $user */
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        $query = $gistRepository->getListQuery($user);
        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('Gist/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }

    #[Route(path: '', name: 'gist_add', methods: ['POST'])]
    public function addAction(Request $request, BotChecker $botChecker, EntityManagerInterface $entityManager, MessageBusInterface $messageBus): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AddType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $botChecker->checkRequest($request);

                    $gist = $form->getData();

                    $gist->setUser($user);
                    $gist->setIp($request->getClientIp());
                    $gist->setBrowser($request->headers->get('User-Agent', ''));

                    $entityManager->persist($gist);
                    $entityManager->flush();

                    $messageBus->dispatch(new GistAddMessage($gist->getId()));
                    $this->addFlash('notice', 'Сообщение успешно добавлено');
                } else {
                    foreach ($form->getErrors(true) as $formError) {
                        $this->addFlash('notice', $formError->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('notice', $e->getMessage());
        }

        return $this->redirectToRoute('gist_index');
    }

    #[Route(path: '/view/{id}', name: 'gist_view', requirements: ['id' => '\d+'])]
    public function viewAction(Gist $gist): Response
    {
        return $this->render('Gist/view.html.twig', [
            'gist' => $gist,
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'gist_delete', requirements: ['id' => '\d+'], options: ['expose' => true], methods: ['POST'])]
    public function deleteAction(Gist $gist, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $gist);

        $entityManager->remove($gist);
        $entityManager->flush();

        // переадресация на главную
        $url = $this->generateUrl('gist_index', [], Router::ABSOLUTE_URL);

        return $this->redirect($url);
    }

    #[Route(path: '/search/{key}', name: 'gist_search', requirements: ['key' => '[a-zA-Z0-9]+'], defaults: ['key' => null])]
    public function searchAction(Request $request, Manticore $manticore, ?string $key = null): Response
    {
        $page = (int) $request->query->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid('', false);
                    $request->getSession()->set('gist_search', [
                        'key' => $key,
                        'data' => $data,
                    ]);
                }

                return $this->redirectToRoute('gist_search', ['key' => $key]);
            }

            if (null !== $key && true === $request->getSession()->has('gist_search')) {
                $search = $request->getSession()->get('gist_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchManticore($manticore, $search['data'], $page);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Gist/search.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ]);
    }

    private function searchManticore(Manticore $client, array $data, int $page = 1): Pagerfanta
    {
        if ('date' === $data['sort']) {
            $orderBy = 'created_at_ts';
        } else {
            $orderBy = 'WEIGHT()';
        }

        return $client->getPage(
            Gist::class,
            'gist',
            ['subject', 'body'],
            $data['search'],
            $page,
            $orderBy,
        );
    }

    #[Route(path: '/edit/{id}', name: 'gist_edit', requirements: ['id' => '\d+'])]
    public function editAction(Request $request, Gist $gist, EntityManagerInterface $entityManager): Response
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
                    $this->editGistData($request, $gist, $newGist, $entityManager);

                    $url = $this->generateUrl('gist_view', ['id' => $gist->getId()], Router::ABSOLUTE_URL);

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Gist/edit.html.twig', [
            'form' => $form->createView(),
            'gist' => $gist,
        ]);
    }

    private function editGistData(Request $request, Gist $data, Gist $newData, EntityManagerInterface $entityManager): void
    {
        $data->setSubject($newData->getSubject());
        $data->setBody($newData->getBody());

        // обновляем ip и браузер только если сообщение редактирует владелец
        if ($data->getUser()->isEqualTo($this->getUser())) {
            // $data->setUser($this->getUser());
            $data->setIp($request->getClientIp());
            $data->setBrowser($request->headers->get('User-Agent', ''));
        }

        $entityManager->persist($data);
        $entityManager->flush();
    }
}
