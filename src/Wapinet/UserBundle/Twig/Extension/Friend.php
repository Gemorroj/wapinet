<?php

namespace Wapinet\UserBundle\Twig\Extension;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
use Wapinet\UserBundle\Entity\User;

class Friend extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var SecurityContext
     */
    protected $context;

    public function __construct(EntityManager $em, SecurityContext $context)
    {
        $this->em = $em;
        $this->context = $context;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_user_friend', array($this, 'isFriend')),
        );
    }

    /**
     * @param User $friend
     *
     * @return bool
     */
    public function isFriend(User $friend)
    {
        $token = $this->context->getToken();
        if (null === $token) {
            return false;
        }
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $friendRepository = $this->em->getRepository('WapinetUserBundle:Friend');
        $isFriend = $friendRepository->findOneBy(array(
            'user' => $user,
            'friend' => $friend,
        ));

        return (null !== $isFriend);
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_user_friend';
    }
}
