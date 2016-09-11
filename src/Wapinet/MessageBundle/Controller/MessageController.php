<?php

namespace Wapinet\MessageBundle\Controller;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wapinet\MessageBundle\Provider\Provider;

class MessageController extends BaseController
{
    /**
     * Displays the authenticated participant inbox
     *
     * @return Response
     */
    public function inboxAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $page = $request->get('page', 1);

        /** @var Provider $provider */
        $provider = $this->getProvider();
        $pagerfanta = $provider->getInboxThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:inbox.html.twig', array(
            'pagerfanta' => $pagerfanta
        ));
    }

    /**
     * Displays the authenticated participant messages sent
     *
     * @return Response
     */
    public function sentAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $page = $request->get('page', 1);

        /** @var Provider $provider */
        $provider = $this->getProvider();
        $pagerfanta = $provider->getSentThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:sent.html.twig', array(
            'pagerfanta' => $pagerfanta
        ));
    }

    /**
     * Displays the authenticated participant deleted threads
     *
     * @return Response
     */
    public function deletedAction()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $page = $request->get('page', 1);

        /** @var Provider $provider */
        $provider = $this->getProvider();
        $pagerfanta = $provider->getDeletedThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:deleted.html.twig', array(
            'pagerfanta' => $pagerfanta
        ));
    }

    /**
     * Create a new message thread
     *
     * @param string $recipient
     * @throws NotFoundHttpException
     * @return Response
     */
    public function newThreadAction($recipient = null)
    {
        /** @var Form $form */
        $form = $this->container->get('fos_message.new_thread_form.factory')->create();
        if (null !== $recipient) {
            $userManager = $this->container->get('fos_user.user_manager');
            $user = $userManager->findUserByUsername($recipient);
            if (null === $user) {
                throw new NotFoundHttpException('Пользователь не найден');
            }
            $form['recipient']->setData($user);
        }

        $formHandler = $this->container->get('fos_message.new_thread_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                'threadId' => $message->getThread()->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:newThread.html.twig', array(
            'form' => $form->createView(),
            'data' => $form->getData()
        ));
    }


    /**
     * Deletes a thread
     *
     * @param string $threadId the thread id
     *
     * @return JsonResponse
     */
    public function deleteAction($threadId)
    {
        parent::deleteAction($threadId);

        return new JsonResponse(array('threadId' => $threadId));
    }

    /**
     * Undeletes a thread
     *
     * @param string $threadId
     *
     * @return JsonResponse
     */
    public function undeleteAction($threadId)
    {
        parent::undeleteAction($threadId);

        return new JsonResponse(array('threadId' => $threadId));
    }
}
