<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\File;
use App\Entity\News;
use App\Entity\User;
use App\Service\Ginfo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController as BaseAdminController;
use FOS\UserBundle\Doctrine\UserManager as UserManagerDoctrine;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends BaseAdminController
{
    public function monitoringAction(Ginfo $ginfo): Response
    {
        $info = $ginfo->getInfo();

        return $this->render('monitoring.html.twig', [
            'info' => [
                'general' => $info->getGeneral(),
                'php' => $info->getPhp(),
                'selinux' => $info->getSelinux(),
                'cpu' => $info->getCpu(),
                'network' => $info->getNetwork() ?: [],
                'disk' => $info->getDisk(),
                'services' => $info->getServices() ?: [],
                'memory' => $info->getMemory(),
            ],
        ]);
    }

    public function createNewUserEntity(UserManagerInterface $userManager): UserInterface
    {
        return $userManager->createUser();
    }

    public function prePersistUserEntity(User $user, UserManagerInterface $userManage): void
    {
        if ($userManage instanceof UserManagerDoctrine) {
            $userManage->updateUser($user, false);
        } else {
            $userManage->updateUser($user);
        }
    }

    public function persistNewsEntity(News $news): void
    {
        $news->setCreatedBy($this->getUser());
        $this->persistEntity($news);
        $this->newsSubscriber($news);
    }

    /**
     * Подписка на новости.
     */
    private function newsSubscriber(News $news): void
    {
        $em = $this->getDoctrine()->getManager();

        $userRepository = $em->getRepository(User::class);
        /** @var User[] $users */
        $users = $userRepository->findBy([
            'enabled' => true,
        ]);

        foreach ($users as $user) {
            $entityEvent = new Event();
            $entityEvent->setSubject('Новость на сайте.');
            $entityEvent->setTemplate('news');
            $entityEvent->setVariables([
                'news' => $news,
            ]);

            $entityEvent->setNeedEmail($user->getSubscriber()->getEmailNews());
            $entityEvent->setUser($user);

            $em->persist($entityEvent);
        }

        $em->flush();
    }

    /**
     * @param object $entity
     */
    protected function preRemoveEntity($entity): void
    {
        switch (true) {
            case $entity instanceof File:
                $this->get(\App\Service\File\File::class)->cleanupFile($entity);
                break;
        }
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            \App\Service\File\File::class,
        ];
    }
}
