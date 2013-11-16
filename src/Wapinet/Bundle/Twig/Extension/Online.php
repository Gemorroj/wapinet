<?php

namespace Wapinet\Bundle\Twig\Extension;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Entity\Online as EntityOnline;

class Online extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_online', array($this, 'getOnline')),
        );
    }

    /**
     * @return int
     */
    public function getOnline()
    {
        $this->updateOnline();
        $this->deleteOnline();

        return $this->em->createQuery('SELECT COUNT(o.id) FROM Wapinet\Bundle\Entity\Online o')->getSingleScalarResult();
    }


    /**
     *
     */
    protected function updateOnline()
    {
        $result = null;
        $token = $this->container->get('security.context')->getToken();
        if (null !== $token) {
            // getUser возвращает string
            $user = $this->container->get('fos_user.user_manager')->findUserByUsername($token->getUsername());
        } else {
            $user = null;
        }

        $online = new EntityOnline;
        $online->setBrowser($this->container->get('request')->headers->get('User-Agent'));
        $online->setIp($this->container->get('request')->getClientIp());
        $online->setDatetime(new \DateTime());
        $online->setUser($user);

        $result = $this->em->createQuery('SELECT o.id FROM Wapinet\Bundle\Entity\Online o WHERE o.ip = :ip AND o.browser = :browser')
            ->setParameter('ip', $online->getIp())
            ->setParameter('browser', $online->getBrowser())
            ->getOneOrNullResult();
        if (null !== $result) {
            $online->setId($result['id']);
            $this->em->merge($online);
        } else {
            if (null !== $online->getUser()) {
                $result = $this->em->createQuery('SELECT o.id FROM Wapinet\Bundle\Entity\Online o WHERE o.user = :user')
                    ->setParameter('user', $online->getUser())
                    ->getOneOrNullResult();

                if (null !== $result) {
                    $online->setId($result['id']);
                    $this->em->merge($online);
                } else {
                    $this->em->persist($online);
                }
            } else {
                $this->em->persist($online);
            }
        }

        $this->em->flush();
    }

    /**
     *
     */
    protected function deleteOnline()
    {
        $this->em->createQuery('DELETE FROM Wapinet\Bundle\Entity\Online o WHERE o.datetime < :lifetime')
            ->setParameter('lifetime', new \DateTime('-' . $this->container->getParameter('wapinet_lifetime') . ' seconds'))
            ->execute();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_online';
    }
}
