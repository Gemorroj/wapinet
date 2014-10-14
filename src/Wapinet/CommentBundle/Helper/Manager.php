<?php
namespace Wapinet\CommentBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;

class Manager
{
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $threadId
     * @throws \Doctrine\DBAL\DBALException
     */
    public function removeThread($threadId)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->doctrine->getConnection();
        $q = $connection->prepare('DELETE FROM comment_vote WHERE comment_id IN(SELECT id FROM comment WHERE thread_id = ?)');
        $q->execute(array($threadId));
        $q = $connection->prepare('DELETE FROM comment WHERE thread_id = ?');
        $q->execute(array($threadId));
        $q = $connection->prepare('DELETE FROM comment_thread WHERE id = ?');
        $q->execute(array($threadId));
    }
}
