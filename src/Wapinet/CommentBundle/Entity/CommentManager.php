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
use Pagerfanta\Pagerfanta;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\HttpFoundation\RequestStack;
use Wapinet\Bundle\Helper\Paginate;
use Wapinet\UserBundle\Entity\User;

/**
 * Default ORM CommentManager.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class CommentManager extends BaseCommentManager
{
    /**
     * @var Paginate
     */
    protected $paginate;
    /**
     * @var RequestStack
     */
    protected $requestStack;


    /**
     * @param User $user
     * @return int
     */
    public function countUserComments(User $user)
    {
        $q = $this->repository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.author = :user')
            ->setParameter('user', $user)
            ->getQuery();

        return $q->getSingleScalarResult();
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

        return $this->paginate->paginate($qb, $page);
    }


    /**
     * Performs persisting of the comment.
     *
     * @param Comment $comment
     */
    protected function doSaveComment(CommentInterface $comment)
    {
        $request = $this->requestStack->getCurrentRequest();
        $comment->setIp($request->getClientIp());
        $comment->setBrowser($request->headers->get('User-Agent', ''));

        $this->em->persist($comment->getThread());
        $this->em->persist($comment);
        $this->em->flush();
    }


    /**
     * @param Paginate $paginate
     */
    public function setPaginate(Paginate $paginate)
    {
        $this->paginate = $paginate;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
}
