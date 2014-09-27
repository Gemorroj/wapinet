<?php

namespace Wapinet\MessageBundle\Provider;

use FOS\MessageBundle\Model\MessageInterface;
use FOS\MessageBundle\Provider\Provider as BaseProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Pagerfanta\Pagerfanta;

/**
 * Provides threads for the current authenticated user
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class Provider extends BaseProvider
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Gets the thread in the inbox of the current user
     *
     * @param int $page
     * @return Pagerfanta
     */
    public function getInboxThreads($page = 1)
    {
        $participant = $this->getAuthenticatedParticipant();

        $builder = $this->threadManager->getParticipantInboxThreadsQueryBuilder($participant);
        return $this->container->get('paginate')->paginate($builder, $page);

        //return $this->threadManager->findParticipantInboxThreads($participant);
    }

    /**
     * Gets the thread in the sentbox of the current user
     *
     * @param int $page
     * @return Pagerfanta
     */
    public function getSentThreads($page = 1)
    {
        $participant = $this->getAuthenticatedParticipant();

        $builder = $this->threadManager->getParticipantSentThreadsQueryBuilder($participant);
        return $this->container->get('paginate')->paginate($builder, $page);

        //return $this->threadManager->findParticipantSentThreads($participant);
    }

    /**
     * Gets the thread in the deleted of the current user
     *
     * @param int $page
     * @return Pagerfanta
     */
    public function getDeletedThreads($page = 1)
    {
        $participant = $this->getAuthenticatedParticipant();

        $builder = $this->threadManager->getParticipantDeletedThreadsQueryBuilder($participant);
        return $this->container->get('paginate')->paginate($builder, $page);

        //return $this->threadManager->findParticipantDeletedThreads($participant);
    }


    /**
     * Gets a thread by its ID
     * Performs authorization checks
     * Marks the thread as read
     *
     * @param int $threadId
     * @param int $page
     *
     * @throws NotFoundHttpException|AccessDeniedException
     * @return array('thread'=>\Wapinet\MessageBundle\Entity\Thread, 'messages'=>Pagerfanta)
     */
    public function getThreadMesages($threadId, $page = 1)
    {
        $thread = $this->threadManager->findThreadById($threadId);
        if (!$thread) {
            throw new NotFoundHttpException('There is no such thread');
        }
        if (!$this->authorizer->canSeeThread($thread)) {
            throw new AccessDeniedException('You are not allowed to see this thread');
        }

        $parerfanta = $this->container->get('paginate')->paginate($thread->getMessages(), $page);


        $currentUser = $this->container->get('security.context')->getToken()->getUser();

        /*
        $currentParticipant = null;
        foreach ($thread->getParticipants() as $participant) {
            if ($currentUser->getId() === $participant->getId()) {
                $currentParticipant = $participant;
                break;
            }
        }
        */

        /** @var MessageInterface $message */
        foreach ($parerfanta->getCurrentPageResults() as $message) {
            $this->messageManager->markAsReadByParticipant($message, $currentUser);
        }

        return array(
            'thread' => $thread,
            'messages' => $parerfanta,
        );
    }


    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
