<?php

namespace Wapinet\MessageBundle\Controller;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


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
            '$pagerfanta' => $pagerfanta
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
            'threads' => $pagerfanta
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
}
