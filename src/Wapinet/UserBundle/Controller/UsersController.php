<?php
namespace Wapinet\UserBundle\Controller;

use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\UserBundle\Form\Type\SearchType;


class UsersController extends Controller
{
    /**
     * @param Request $request
     * @param string|null $key
     * @return Response|RedirectResponse
     */
    public function indexAction(Request $request, $key = null)
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(new SearchType());
        $pagerfanta = null;
        $session = $this->get('session');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = uniqid();
                    $session->set('users_search', array(
                        'key' => $key,
                        'data' => $data
                    ));
                }

                return $this->redirect(
                    $this->get('router')->generate('wapinet_users', array('key' => $key), Router::ABSOLUTE_URL)
                );
            }

            if (null !== $key && true === $session->has('users_search')) {
                $search = $session->get('users_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->search($search['data'], $page);
                }
            } else {
                $pagerfanta = $this->online($page);
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetUserBundle:Users:index.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ));
    }


    /**
     * @param int $page
     * @return Pagerfanta
     */
    protected function online ($page = 1)
    {
        $userRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:User');
        $query = $userRepository->getOnlineUsersQuery();

        return $this->get('paginate')->paginate($query, $page);
    }

    /**
     * @param array $data
     * @param int $page
     * @return Pagerfanta
     */
    protected function search (array $data, $page = 1)
    {
        $userRepository = $this->getDoctrine()->getRepository('WapinetUserBundle:User');
        $query = $userRepository->getSearchUsersQuery(
            $data['search'],
            $data['use_info'],
            $data['only_online'],
            $data['sex'],
            $data['created_after'],
            $data['created_before']
        );

        return $this->get('paginate')->paginate($query, $page);
    }
}
