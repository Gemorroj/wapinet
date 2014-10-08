<?php
namespace Wapinet\Bundle\Listener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Wapinet\Bundle\Entity\Online;
use Wapinet\UserBundle\Entity\User;

class OnlineListener
{
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Update online
     * @param FilterControllerEvent $event
     */
    public function onCoreController(FilterControllerEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->em->createQuery('DELETE FROM Wapinet\Bundle\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', new \DateTime('now -' . User::LIFETIME))
            ->execute();


        $request = $event->getRequest();

        $online = new Online();
        $online->setPath($request->getPathInfo());
        $online->setBrowser($request->headers->get('User-Agent', ''));
        $online->setIp($request->getClientIp());
        $online->setDatetime(new \DateTime());

        $issetRow = $this->em->createQuery('SELECT o.id FROM Wapinet\Bundle\Entity\Online o WHERE o.ip = :ip AND o.browser = :browser')
            ->setParameter('ip', $online->getIp())
            ->setParameter('browser', $online->getBrowser())
            ->getOneOrNullResult();

        if (null !== $issetRow) {
            $online->setId($issetRow['id']);
            $this->em->merge($online);
        } else {
            $this->em->persist($online);
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            // могут быть конкурентные запросы, которые запишут в онлайн данные на уникальном индексе
            // игнорируем, т.к. маловажно
        }
    }
}
