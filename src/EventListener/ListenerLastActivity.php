<?php
namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ListenerLastActivity
{
    protected $container;
    protected $em;

    /**
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    /**
     * Update the user "lastActivity" on each request
     * @param FilterControllerEvent $event
     */
    public function onCoreController(FilterControllerEvent $event)
    {
        // Here we are checking that the current request is a "MASTER_REQUEST", and ignore any subrequest in the process (for example when doing a render() in a twig template)
        if (!$event->isMasterRequest()) {
            return;
        }

        // We are checking a token authentification is available before using the User
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token) {
            /** @var User $user */
            $user = $token->getUser();
            if (\is_object($user) && $user instanceof User) {
                // We are using a delay during wich the user will be considered as still active, in order to avoid too much UPDATE in the database
                $delay = new \DateTime($this->container->getParameter('wapinet_user_last_activity_delay') . ' seconds ago');

                // We are checking the User class in order to be certain we can call "getLastActivity".
                if ($user->getLastActivity() < $delay) {
                    $user->setLastActivity(new \DateTime());
                    $this->em->flush($user);
                }
            }
        }
    }
}
