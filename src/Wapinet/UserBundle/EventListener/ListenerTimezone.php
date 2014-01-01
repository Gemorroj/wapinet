<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Wapinet\UserBundle\Entity\User;

/**
 * Timezone Listener
 */
class ListenerTimezone
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @param FilterControllerEvent $event
     * @throws \RuntimeException
     */
    public function onCoreController(FilterControllerEvent $event)
    {
        if ($event->getRequestType() !== HttpKernel::MASTER_REQUEST) {
            return;
        }

        if (!$this->securityContext->getToken()) {
            return;
        }

        $user = $this->securityContext->getToken()->getUser();
        if (!is_object($user) || !($user instanceof User)) {
            return;
        }

        if (!$user->getTimezone()) {
            return;
        }

        if (!date_default_timezone_set($user->getTimezone())) {
            throw new \RuntimeException('Не удалось изменить временную зону.');
        }
    }
}
