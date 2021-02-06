<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\Type\User\SearchType;
use App\Repository\UserRepository;
use App\Service\Manticore;
use App\Service\Paginate;
use Exception;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UsersController extends AbstractController
{
    /**
     * @Route("/users/{key}", name="wapinet_users", defaults={"key": null}, requirements={"key": "[a-zA-Z0-9]+"})
     */
    public function indexAction(Request $request, SessionInterface $session, ?string $key = null): Response
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid('', false);
                    $session->set('users_search', [
                        'key' => $key,
                        'data' => $data,
                    ]);
                }

                return $this->redirectToRoute('wapinet_users', ['key' => $key]);
            }

            if (null !== $key && true === $session->has('users_search')) {
                $search = $session->get('users_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchManticore($search['data'], $page);
                }
            } else {
                $pagerfanta = $this->online($page);
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('User/Users/index.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ]);
    }

    private function online(int $page = 1): Pagerfanta
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $query = $userRepository->getOnlineUsersQuery();

        return $this->get(Paginate::class)->paginate($query, $page);
    }

    private function searchManticore(array $data, int $page = 1): Pagerfanta
    {
        /** @var Manticore $client */
        $client = $this->get(Manticore::class);
        $sphinxQl = $client->select($page)
            ->from(['users'])
            ->match(['username', 'email', 'info'], $data['search'])
        ;

        return $client->getPagerfanta($sphinxQl, User::class);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Manticore::class] = '?'.Manticore::class;
        $services[Paginate::class] = Paginate::class;

        return $services;
    }
}
