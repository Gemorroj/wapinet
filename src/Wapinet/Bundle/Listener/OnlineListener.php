<?php
namespace Wapinet\Bundle\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Wapinet\Bundle\Entity\Online;

class OnlineListener
{
    protected $container;
    protected $em;

    /**
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    /**
     * Update online
     * @param FilterControllerEvent $event
     */
    public function onCoreController(FilterControllerEvent $event)
    {
        if ($event->isMasterRequest()) {
            return;
        }

        $this->em->createQuery('DELETE FROM Wapinet\Bundle\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', new \DateTime('-' . $this->container->getParameter('wapinet_lifetime') . ' seconds'))
            ->execute();


        $online = new Online;
        $online->setBrowser($this->container->get('request')->headers->get('User-Agent', ''));
        $online->setIp($this->container->get('request')->getClientIp());
        $online->setDatetime(new \DateTime());

        $result = $this->em->createQuery('SELECT o.id FROM Wapinet\Bundle\Entity\Online o WHERE o.ip = :ip AND o.browser = :browser')
            ->setParameter('ip', $online->getIp())
            ->setParameter('browser', $online->getBrowser())
            ->getOneOrNullResult();
        if (null !== $result) {
            $online->setId($result['id']);
            $this->em->merge($online);
        } else {
            $this->em->persist($online);
        }

        $this->em->flush();
    }
}
