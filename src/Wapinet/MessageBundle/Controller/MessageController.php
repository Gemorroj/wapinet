<?php

namespace Wapinet\MessageBundle\Controller;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MessageController extends BaseController
{
    /**
     * Displays the authenticated participant inbox
     *
     * @return Response
     */
    public function inboxAction()
    {
        $request = $this->container->get('request');
        $page = $request->get('page', 1);
        $pagerfanta = $this->getProvider()->getInboxThreads($page);

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
        $request = $this->container->get('request');
        $page = $request->get('page', 1);
        $pagerfanta = $this->getProvider()->getSentThreads($page);

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
        $request = $this->container->get('request');
        $page = $request->get('page', 1);
        $pagerfanta = $this->getProvider()->getDeletedThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:deleted.html.twig', array(
            'pagerfanta' => $pagerfanta
        ));
    }

    /**
     * Displays a thread, also allows to reply to it
     *
     * @param string $threadId the thread id
     *
     * @return Response
     */
    public function threadAction($threadId)
    {
        $request = $this->container->get('request');
        $page = $request->get('page', 1);
        /** @var array('thread'=>\Wapinet\MessageBundle\Entity\Thread, 'messages'=>Pagerfanta) $threadMessages */
        $threadMessages = $this->getProvider()->getThreadMesages($threadId, $page);

        $form = $this->container->get('fos_message.reply_form.factory')->create($threadMessages['thread']);
        $formHandler = $this->container->get('fos_message.reply_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                'threadId' => $threadMessages['thread']->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:thread.html.twig', array(
            'pagerfanta' => $threadMessages['messages'],
            'form' => $form->createView(),
            'thread' => $threadMessages['thread']
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
     * @return RedirectResponse|JsonResponse
     */
    public function deleteAction($threadId)
    {
        $thread = $this->getProvider()->getThread($threadId);
        $this->container->get('fos_message.deleter')->markAsDeleted($thread);
        $this->container->get('fos_message.thread_manager')->saveThread($thread);

        $url = $this->container->get('router')->generate('wapinet_message_inbox');

        if (true === $this->container->get('request')->isXmlHttpRequest()) {
            return new JsonResponse(array('url' => $url));
        }

        return new RedirectResponse($url);
    }

    /**
     * Undeletes a thread
     *
     * @param string $threadId
     *
     * @return RedirectResponse
     */
    public function undeleteAction($threadId)
    {
        $thread = $this->getProvider()->getThread($threadId);
        $this->container->get('fos_message.deleter')->markAsUndeleted($thread);
        $this->container->get('fos_message.thread_manager')->saveThread($thread);

        $url = $this->container->get('router')->generate('wapinet_message_inbox');

        if (true === $this->container->get('request')->isXmlHttpRequest()) {
            return new JsonResponse(array('url' => $url));
        }

        return new RedirectResponse($url);
    }
}
