<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\File;
use App\Entity\News;
use App\Entity\User;
use App\Helper\Ginfo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use FOS\UserBundle\Doctrine\UserManager as UserManagerDoctrine;
use FOS\UserBundle\Model\UserManagerInterface;

class AdminController extends BaseAdminController
{
    /**
     * @param Ginfo $ginfo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function monitoringAction(Ginfo $ginfo)
    {
        $info = $ginfo->getInfo();
        return $this->render('monitoring.html.twig', [
            'info' => [
                'general' => $info->getGeneral(),
                'php' => $info->getPhp(),
                'selinux' => $info->getSelinux(),
                'cpu' => $info->getCpu(),
                'network' => $info->getNetwork(),
                'disk' => $info->getDisk(),
                'services' => $info->getServices(),
                'memory' => $info->getMemory(),
            ],
        ]);
    }

    /**
     * @param UserManagerInterface $userManager
     * @return \FOS\UserBundle\Model\UserInterface
     */
    public function createNewUserEntity(UserManagerInterface $userManager)
    {
        return $userManager->createUser();
    }

    /**
     * @param User $user
     * @param UserManagerInterface $userManage
     */
    public function prePersistUserEntity(User $user, UserManagerInterface $userManage)
    {
        if ($userManage instanceof UserManagerDoctrine) {
            $userManage->updateUser($user, false);
        } else {
            $userManage->updateUser($user);
        }
    }


    /**
     * @param News $news
     */
    public function persistNewsEntity(News $news)
    {
        $news->setCreatedBy($this->getUser());
        parent::persistEntity($news);
        $this->newsSubscriber($news);
    }

    /**
     * Подписка на новости
     *
     * @param News $news
     */
    private function newsSubscriber(News $news)
    {
        $em = $this->get('doctrine.orm.entity_manager');

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
    protected function preRemoveEntity($entity)
    {
        switch (true) {
            case $entity instanceof File:
                $this->get('file')->cleanupFile($entity);
                break;
        }
    }
}