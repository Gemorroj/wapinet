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
use FOS\CommentBundle\Model\CommentInterface;
use FOS\CommentBundle\Model\ThreadInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Query\Expr;
use Wapinet\UserBundle\Entity\User;

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
     * @return array('pagerfanta' => \Pagerfanta\Pagerfanta, 'comments' => array)
     */
    public function findCommentsByThread(ThreadInterface $thread, $depth = null, $sorterAlias = null, $page = 1)
    {
        $qb = $this->repository->createQueryBuilder('c');

        $qb->join('c.thread', 't')
            ->where('t.id = :thread')
            ->orderBy('c.ancestors', 'ASC')
            ->setParameter('thread', $thread->getId());

        if (null !== $depth && $depth >= 0) {
            // Queries for an additional level so templates can determine
            // if the final 'depth' layer has children.

            $qb->andWhere('c.depth < :depth')
               ->setParameter('depth', $depth + 1);
        }

        // TODO: https://github.com/FriendsOfSymfony/FOSCommentBundle/issues/113
        //$expr = $qb->expr();

        //$concatLeft = $expr->concat($expr->literal('%/'), $expr->concat('c.id', $expr->literal('%')));
        //$concatRight = $expr->concat($expr->literal('%'), $expr->concat('c.id', $expr->literal('/%')));

        //$qb->addSelect('ca')
        //    ->leftJoin('Wapinet\CommentBundle\Entity\Comment', 'ca')
        //    ->where('ca.ancestors = c.id')
        //    ->orWhere($expr->like('ca.ancestors', $concatLeft))
        //    ->orWhere($expr->like('ca.ancestors', $concatRight));

        //$pagerfanta = $this->container->get('paginate')->paginate($qb, $page);
        //$comments = $pagerfanta->CurrentPageResults();
        $comments = $qb
            ->getQuery()
            ->execute();


        if (null !== $sorterAlias) {
            $sorter = $this->sortingFactory->getSorter($sorterAlias);
            $comments = $sorter->sortFlat($comments);
        }

        //$pagerfanta = $this->container->get('paginate')->paginate($comments, $page);
        //$comments = $pagerfanta->getCurrentPageResults();

        return array('pagerfanta' => /*$pagerfanta*/null, 'comments' => $comments);
    }


    /**
     * @param ThreadInterface $thread
     * @param null            $sorter
     * @param null            $depth
     * @param int             $page
     *
     * @return array('pagerfanta' => \Pagerfanta\Pagerfanta, 'comments' => array)
     */
    public function findCommentTreeByThread(ThreadInterface $thread, $sorter = null, $depth = null, $page = 1)
    {
        $commentsByThread = $this->findCommentsByThread($thread, $depth, null, $page);
        $sorter = $this->sortingFactory->getSorter($sorter);

        $commentsByThread['comments'] = $this->organiseComments($commentsByThread['comments'], $sorter);
        return $commentsByThread;
    }


    /**
     * @param User $user
     * @param integer         $depth
     * @param int $page
     * @return Pagerfanta
     */
    public function findCommentsByUser(User $user, $depth = null, $page = 1)
    {
        $qb = $this->repository->createQueryBuilder('c');

        $qb->where('c.author = :user')
            ->setParameter('user', $user)
            ->join('c.thread', 't')
            ->orderBy('c.id', 'DESC');

        if (null !== $depth && $depth >= 0) {
            // Queries for an additional level so templates can determine
            // if the final 'depth' layer has children.

            $qb->andWhere('c.depth < :depth')
                ->setParameter('depth', $depth + 1);
        }

        return $this->container->get('paginate')->paginate($qb, $page);
    }


    /**
     * Performs persisting of the comment.
     *
     * @param CommentInterface $comment
     */
    protected function doSaveComment(CommentInterface $comment)
    {
        $request = $this->container->get('request');
        $comment->setIp($request->getClientIp());
        $comment->setBrowser($request->headers->get('User-Agent', ''));

        $this->em->persist($comment->getThread());
        $this->em->persist($comment);
        $this->em->flush();
    }


    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
