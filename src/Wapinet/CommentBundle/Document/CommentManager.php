<?php

/**
 * This file is part of the FOSCommentBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Wapinet\CommentBundle\Document;

use FOS\CommentBundle\Document\CommentManager as BaseCommentManager;
use FOS\CommentBundle\Model\ThreadInterface;

/**
 * Default ODM CommentManager.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class CommentManager extends BaseCommentManager
{
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
            ->createQueryBuilder()
            ->field('thread.$id')->equals($thread->getId())
            ->sort('ancestors', 'ASC');

        if ($depth > 0) {
            // Queries for an additional level so templates can determine
            // if the final 'depth' layer has children.

            $qb->field('depth')->lte($depth + 1);
        }

        $comments = $qb
            ->getQuery()
            ->execute();

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
        $comments = $this->findCommentsByThread($thread, $depth);
        $sorter = $this->sortingFactory->getSorter($sorter);

        return $this->organiseComments($comments, $sorter);
    }
}
