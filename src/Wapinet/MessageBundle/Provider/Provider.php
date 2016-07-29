<?php

namespace Wapinet\MessageBundle\Provider;

use FOS\MessageBundle\Model\MessageInterface;
use FOS\MessageBundle\Provider\Provider as BaseProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Pagerfanta\Pagerfanta;
use Wapinet\UserBundle\Entity\User;

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
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
