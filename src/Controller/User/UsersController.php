<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Form\Type\User\SearchType;
use App\Repository\UserRepository;
use App\Service\Manticore;
use App\Service\Paginate;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UsersController extends AbstractController
{
    /**
     * @Route("/users/{key}", name="wapinet_users", defaults={"key": null}, requirements={"key": "[a-zA-Z0-9]+"})
     */
    public function indexAction(Request $request, UserRepository $userRepository, Paginate $paginate, Manticore $manticore, ?string $key = null): Response
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
                    $request->getSession()->set('users_search', [
                        'key' => $key,
                        'data' => $data,
                    ]);
                }

                return $this->redirectToRoute('wapinet_users', ['key' => $key]);
            }

            if (null !== $key && true === $request->getSession()->has('users_search')) {
                $search = $request->getSession()->get('users_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);

                    $sphinxQl = $manticore->select($page)
                       ->from(['users'])
                       ->match(['username', 'email', 'info'], $search['data']['search'])
                    ;

                    $pagerfanta = $manticore->getPagerfanta($sphinxQl, User::class);
                }
            } else {
                $query = $userRepository->getOnlineUsersQuery();
                $pagerfanta = $paginate->paginate($query, $page);
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
}
