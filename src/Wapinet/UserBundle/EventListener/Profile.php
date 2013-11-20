<?php
namespace Wapinet\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Wapinet\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Profile implements EventSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'edit',
        );
    }

    public function edit(FormEvent $event)
    {
        $formName = $this->container->getParameter('fos_user.profile.form.name');
        $formData = $event->getRequest()->get($formName);
        /** @var User $formUser */
        $formUser = $event->getForm()->getData();

        /** @var User $user */
        $user = $this->container->get('security.context')->getToken()->getUser();

        file_put_contents('/log0.log', print_r($user->hasAvatar() ? 1 : 0, true));
        if ($user->hasAvatar()) {
            if (isset($formData['avatar_delete']) && $formData['avatar_delete']) {
                file_put_contents('/log.log', print_r('del', true));
                if (!@unlink($user->getAvatar()->getLocalPath())) {
                    throw new FileException('Не удалось удалить файл');
                }
            } else {
                file_put_contents('/log1.log', print_r('else', true));
                if (!$formUser->hasAvatar()) {
                    file_put_contents('/log2.log', print_r('no has', true));
                    $formUser->setAvatar($user->getAvatar());
                }
            }
        }

        //$user->setAvatar(null);
        //$this->em->persist($user);
        //$this->em->flush($user);
    }
}
