<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\Bundle\Entity\Gist;
use Wapinet\Bundle\Event\GistEvent;
use Wapinet\Bundle\Form\Type\Gist\AddType;
use Wapinet\UserBundle\Entity\User;

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


    protected function saveAcl(User $user, Gist $gist)
    {
        // creating the ACL
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($gist);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($user);

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $aclProvider->updateAcl($acl);
    }


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
}
