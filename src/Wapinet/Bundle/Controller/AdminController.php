<?php

namespace Wapinet\Bundle\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Entity\Gist;
use Wapinet\Bundle\Entity\News;
use Wapinet\UserBundle\Entity\Event;
use Wapinet\UserBundle\Entity\User;

class AdminController extends BaseAdminController
{
    /**
     * @return \FOS\UserBundle\Model\UserInterface
     */
    public function createNewUserEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    /**
     * @param User $user
     */
    public function prePersistUserEntity(User $user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
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

        $userRepository = $em->getRepository('WapinetUserBundle:User');
        $users = $userRepository->findBy(array(
            'enabled' => true,
            'locked' => false,
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
