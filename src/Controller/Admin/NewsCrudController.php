<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\News;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class NewsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return News::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'subject', 'body']);
    }

    public function configureFields(string $pageName): iterable
    {
        $subject = TextField::new('subject', 'Заголовок');
        $body = TextareaField::new('body');
        $id = IdField::new('id', 'ID');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt', 'Дата обновления');
        $createdBy = AssociationField::new('createdBy');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$subject, $createdAt, $updatedAt];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $subject, $body, $createdAt, $updatedAt, $createdBy];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$subject, $body];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$subject, $body];
        }
    }

    /**
     * Создание новости.
     *
     * @return News
     */
    public function createEntity(string $entityFqcn)
    {
        $news = new News();
        $news->setCreatedBy($this->getUser());
        $this->newsSubscriber($news);

        return $news;
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
    }
}