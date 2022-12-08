<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(priority: -1)]
class LastActivityListener
{
    public function __construct(private ParameterBagInterface $parameterBag, private TokenStorageInterface $tokenStorage, private EntityManagerInterface $entityManager, private ManagerRegistry $managerRegistry)
    {
    }

    /**
     * Update the user "lastActivity" on each request.
     */
    public function __invoke(ControllerEvent $event): void
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

                    try {
                        // fixme: avoid the stupid doctrine error
                        if ($user->getPanel()) {
                            $this->entityManager->initializeObject($user->getPanel());
                        }
                        if ($user->getSubscriber()) {
                            $this->entityManager->initializeObject($user->getSubscriber());
                        }

                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                    } catch (\Exception $e) {
                        $this->managerRegistry->resetManager();
                        // игнорируем, т.к. маловажно
                    }
                }
            }
        }
    }
}
