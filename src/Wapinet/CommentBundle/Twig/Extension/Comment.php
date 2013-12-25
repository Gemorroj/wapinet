<?php

namespace Wapinet\CommentBundle\Twig\Extension;

use Symfony\Component\Security\Core\SecurityContext;
use Wapinet\CommentBundle\Entity\CommentManager;
use Wapinet\UserBundle\Entity\User;

class Comment extends \Twig_Extension
{
    /**
     * @var CommentManager
     */
    protected $commentManager;

    public function __construct(CommentManager $commentManager)
    {
        $this->commentManager = $commentManager;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_comment_count_comments', array($this, 'countComments')),
        );
    }


    /**
     * @param User $user
     * @return int
     */
    public function countComments(User $user)
    {
        return $this->commentManager->countUserComments($user);
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_comment';
    }
}
