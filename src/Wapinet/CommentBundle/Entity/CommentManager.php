<?php

/**
 * This file is part of the FOSCommentBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Wapinet\CommentBundle\Entity;

use FOS\CommentBundle\Entity\CommentManager as BaseCommentManager;
use FOS\CommentBundle\Model\ThreadInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default ORM CommentManager.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class CommentManager extends BaseCommentManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Returns a flat array of comments of a specific thread.
     *
     * @param  ThreadInterface $thread
     * @param  integer         $depth
     * @param  string          $sorterAlias
     * @param  int             $page
     * @return array           of ThreadInterface
     */
    public function findCommentsByThread(ThreadInterface $thread, $depth = null, $sorterAlias = null, $page = 1)
    {
        $qb = $this->repository
                ->createQueryBuilder('c')
                ->join('c.thread', 't')
                ->where('t.id = :thread')
                ->orderBy('c.ancestors', 'ASC')
                ->setParameter('thread', $thread->getId());

        if (null !== $depth && $depth >= 0) {
            // Queries for an additional level so templates can determine
            // if the final 'depth' layer has children.

            $qb->andWhere('c.depth < :depth')
               ->setParameter('depth', $depth + 1);
        }

        $comments = $this->container->get('wapinet.paginate.controller')->paginate($qb, $page);
        //$comments = $qb
        //    ->getQuery()
        //    ->execute();

        if (null !== $sorterAlias) {
            $sorter = $this->sortingFactory->getSorter($sorterAlias);
            $comments = $sorter->sortFlat($comments);
        }

        return $comments;
    }


    /**
     * @param ThreadInterface $thread
     * @param null            $sorter
     * @param null            $depth
     * @param int             $page
     *
     * @return array
     */
    public function findCommentTreeByThread(ThreadInterface $thread, $sorter = null, $depth = null, $page = 1)
    {
        $comments = $this->findCommentsByThread($thread, $depth, null, $page);
        $sorter = $this->sortingFactory->getSorter($sorter);

        return $this->organiseComments($comments, $sorter);
    }


    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
