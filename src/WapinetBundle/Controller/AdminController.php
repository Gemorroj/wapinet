<?php

namespace WapinetBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use FOS\UserBundle\Doctrine\UserManager as UserManagerDoctrine;
use FOS\UserBundle\Model\UserManagerInterface;
use WapinetBundle\Entity\Event;
use WapinetBundle\Entity\File;
use WapinetBundle\Entity\News;
use WapinetBundle\Entity\User;

class AdminController extends BaseAdminController
{
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
    public function prePersistNewsEntity(News $news)
    {
        $news->setCreatedBy($this->get('security.token_storage')->getToken()->getUser());
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
        $users = $userRepository->findBy(array(
            'enabled' => true,
        ));

        foreach ($users as $user) {
            $entityEvent = new Event();
            $entityEvent->setSubject('Новость на сайте.');
            $entityEvent->setTemplate('news');
            $entityEvent->setVariables(array(
                'news' => $news,
            ));

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
