<?php

namespace Wapinet\MessageBundle\Controller;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\HttpFoundation\Response;


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

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:inbox.html.twig', array(
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

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:sent.html.twig', array(
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

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:deleted.html.twig', array(
            'threads' => $threads
        ));
    }
}
