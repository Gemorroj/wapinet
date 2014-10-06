<?php

namespace Wapinet\Bundle\Controller;

use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\Bundle\Entity\Gist;
use Wapinet\Bundle\Event\GistEvent;
use Wapinet\Bundle\Form\Type\Gist\AddType;
use Wapinet\Bundle\Form\Type\Gist\EditType;
use Wapinet\Bundle\Form\Type\Gist\SearchType;
use Wapinet\UserBundle\Entity\User;

class GistController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
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


    /**
     * @param Request $request
     * @param string $username
     * @return Response
     */
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

                    $this->saveAcl($user, $gist);

                    $this->container->get('event_dispatcher')->dispatch(
                        GistEvent::GIST_ADD,
                        new GistEvent($this->getUser(), $gist)
                    );
                    $this->get('session')->getFlashBag()->add('notice', 'Сообщение успешно добавлено');
                }
            }
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('notice', $e->getMessage());
        }

        return $this->redirect($this->get('router')->generate('gist_index', array(), Router::ABSOLUTE_URL));
    }


    /**
     * @param User $user
     * @param Gist $gist
     * @throws \Exception
     * @throws \Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    protected function saveAcl(User $user, Gist $gist)
    {
        // creating the ACL
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($gist);

        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\AclNotFoundException $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }

        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($user);

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $aclProvider->updateAcl($acl);
    }


    /**
     * @param int $id
     * @return Response
     */
    public function viewAction($id)
    {
        $gistManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $gist = $gistManager->find($id);
        if (null === $gist) {
            $this->createNotFoundException('Сообщение не найдено.');
        }

        return $this->render('WapinetBundle:Gist:view.html.twig', array(
            'comments_id' => 'gist-' . $gist->getId(),
            'gist' => $gist,
        ));
    }


    /**
     * @param Request $request
     * @param int $id
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|JsonResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $gist = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist')->find($id);
        if (null === $gist) {
            throw $this->createNotFoundException('Сообщение не найдено.');
        }

        $securityContext = $this->get('security.context');
        if (false === $securityContext->isGranted('DELETE', $gist)) {
            throw new AccessDeniedException();
        }

        // БД
        $em = $this->getDoctrine()->getManager();
        $em->remove($gist);
        $em->flush();

        // переадресация на главную
        $router = $this->container->get('router');
        $url = $router->generate('gist_index', array(), Router::ABSOLUTE_URL);

        if (true === $request->isXmlHttpRequest()) {
            return new JsonResponse(array('url' => $url));
        }

        return new RedirectResponse($url);
    }


    /**
     * @param Request $request
     * @param string|null $key
     * @return Response|RedirectResponse
     */
    public function searchAction(Request $request, $key = null)
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
                    $session->set('gist_search', array(
                        'key' => $key,
                        'data' => $data
                    ));
                }

                return $this->redirect(
                    $this->get('router')->generate('gist_search', array('key' => $key), Router::ABSOLUTE_URL)
                );
            }

            if (null !== $key && true === $session->has('gist_search')) {
                $search = $session->get('gist_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchSphinx($search['data'], $page);
                    //$pagerfanta = $this->search($search['data'], $page);
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
        $client = $this->container->get('sphinx');
        $client->setPage($page);

        if ('date' === $data['sort']) {
            $client->SetSortMode(SPH_SORT_ATTR_DESC, 'created_at_ts');
        } else {
            $client->SetSortMode(SPH_SORT_RELEVANCE);
        }

        $client->AddQuery($data['search'], 'gist');

        $result = $client->RunQueries();
        if (false === $result) {
            throw new \RuntimeException($client->GetLastError());
        }

        return $client->getPagerfanta($result, 'Wapinet\Bundle\Entity\Gist');
    }


    /**
     * @param Request $request
     * @param int $id
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|JsonResponse|Response
     */
    public function editAction(Request $request, $id)
    {
        $repository = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $gist = $repository->find($id);
        if (null === $gist) {
            throw $this->createNotFoundException('Сообщение не найдено.');
        }

        $securityContext = $this->get('security.context');
        if (false === $securityContext->isGranted('EDIT', $gist)) {
            throw new AccessDeniedException();
        }


        $form = $this->createForm(new EditType());
        $form['body']->setData($gist->getBody());
        $form['subject']->setData($gist->getSubject());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $this->editGistData($request, $gist, $data);

                    $router = $this->container->get('router');
                    $url = $router->generate('gist_view', array('id' => $gist->getId()), Router::ABSOLUTE_URL);

                    if (true === $request->isXmlHttpRequest()) {
                        return new JsonResponse(array('url' => $url));
                    }

                    return new RedirectResponse($url);
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
     * @param array   $newData
     * @return Gist
     */
    protected function editGistData(Request $request, Gist $data, array $newData)
    {
        $data->setSubject($newData['subject']);
        $data->setBody($newData['body']);

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
