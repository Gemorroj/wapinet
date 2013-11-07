<?php

namespace Wapinet\MessageBundle\Controller;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


class MessageController extends BaseController
{
    /**
     * Displays the authenticated participant inbox
     *
     * @param int $page
     * @return Response
     */
    public function inboxAction($page = 1)
    {
        $threads = $this->getProvider()->getInboxThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:inbox.html.twig', array(
            'threads' => $threads
        ));
    }

    /**
     * Displays the authenticated participant messages sent
     *
     * @param int $page
     * @return Response
     */
    public function sentAction($page = 1)
    {
        $threads = $this->getProvider()->getSentThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:sent.html.twig', array(
            'threads' => $threads
        ));
    }

    /**
     * Displays the authenticated participant deleted threads
     *
     * @param int $page
     * @return Response
     */
    public function deletedAction($page = 1)
    {
        $threads = $this->getProvider()->getDeletedThreads($page);

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:deleted.html.twig', array(
            'threads' => $threads
        ));
    }

    /**
     * Displays a thread, also allows to reply to it
     *
     * @param string $threadId the thread id
     * @param int $page
     *
     * @return Response
     */
    public function threadAction($threadId, $page = 1)
    {
        /** @var \Wapinet\MessageBundle\Entity\Thread $thread */
        $thread = $this->getProvider()->getThread($threadId);

        $messages = $this->container->get('wapinet.paginate.controller')->paginate($thread->getMessages(), $page);


        $form = $this->container->get('fos_message.reply_form.factory')->create($thread);
        $formHandler = $this->container->get('fos_message.reply_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                'threadId' => $message->getThread()->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('WapinetMessageBundle:Message:thread.html.twig', array(
            'messages' => $messages,
            'form' => $form->createView(),
            'thread' => $thread
        ));
    }
}
