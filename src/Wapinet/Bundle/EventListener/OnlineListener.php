<?php
namespace Wapinet\Bundle\EventListener;

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

        // чистим случайным образом, чтобы разгрузить БД
        if (\mt_rand(1, 10) === 1) {
            $this->cleanupOnline();
        }

        $request = $event->getRequest();

        $requestIp = $request->getClientIp();
        $requestBrowser = $request->headers->get('User-Agent', '');

        /** @var Online|null $online */
        $online = $this->em->getRepository('WapinetBundle:Online')->findOneBy(array('ip' => $requestIp, 'browser' => $requestBrowser));

        if (null === $online) {
            $online = new Online();
            $online->setBrowser($requestBrowser);
            $online->setIp($requestIp);
        }
        $online->setDatetime(new \DateTime());
        $online->setPath($request->getPathInfo());

        $this->em->persist($online);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            // могут быть конкурентные запросы, которые запишут в онлайн данные на уникальном индексе
            // игнорируем, т.к. маловажно
        }
    }


    /**
     * Cleanup online
     */
    private function cleanupOnline()
    {
        $this->em->createQuery('DELETE FROM Wapinet\Bundle\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', new \DateTime('now -' . User::LIFETIME))
            ->execute();
    }
}
