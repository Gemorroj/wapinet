<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ListenerLastActivity
{
    public function __construct(private ParameterBagInterface $parameterBag, private TokenStorageInterface $tokenStorage, private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Update the user "lastActivity" on each request.
     */
    public function onCoreController(ControllerEvent $event): void
    {
        // Here we are checking that the current request is a "MASTER_REQUEST", and ignore any subrequest in the process (for example when doing a render() in a twig template)
        if (!$event->isMainRequest()) {
            return;
        }

        // We are checking a token authentification is available before using the User
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                // We are using a delay during wich the user will be considered as still active, in order to avoid too much UPDATE in the database
                $delay = new \DateTime($this->parameterBag->get('wapinet_user_last_activity_delay').' seconds ago');

                // We are checking the User class in order to be certain we can call "getLastActivity".
                if ($user->getLastActivity() < $delay) {
                    $user->setLastActivity(new \DateTime());

                    //$this->entityManager->persist($user);
                    try {
                        //$this->entityManager->flush();
                    } catch (\Exception $e) {
                        // игнорируем, т.к. маловажно
                    }
                }
            }
        }
    }
}
