<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\CommentBundle\Events as Event;
use FOS\CommentBundle\Event\CommentEvent;
use Wapinet\UserBundle\Entity\Event as EntityEvent;
use Doctrine\Orm\EntityManager;
use Wapinet\UserBundle\Entity\User;

class EventComment implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Event::COMMENT_POST_PERSIST => 'create',
        );
    }


    /**
     * @param CommentEvent $event
     */
    public function create(CommentEvent $event)
    {
        $comment = $event->getComment();
        $parentComment = $comment->getParent();
        if (null === $parentComment) {
            return;
        }

        /** @var User $user */
        $user = $parentComment->getAuthor();
        if (null === $user) {
            return;
        }

        $entityEvent = new EntityEvent();
        $entityEvent->setSubject('Поступил ответ на Ваш комментарий.');
        $entityEvent->setTemplate('comment');
        $entityEvent->setVariables(array(
            'parent_comment' => $parentComment,
            'comment' => $comment,
        ));
        $entityEvent->setNeedEmail($user->getSubscriber()->getEmailComments());
        $entityEvent->setUser($user);

        $this->em->persist($entityEvent);
        $this->em->flush();
    }
}
