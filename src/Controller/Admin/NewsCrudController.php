<?php

namespace App\Controller\Admin;

use App\Entity\News;
use Doctrine\Persistence\ManagerRegistry;
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
            ->setSearchFields(['id', 'subject', 'body'])
            ->setDefaultSort(['id' => 'DESC']);
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

        return [];
    }

    public function createEntity(string $entityFqcn): News
    {
        $news = new News();
        $news->setCreatedBy($this->getUser());

        return $news;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[ManagerRegistry::class] = ManagerRegistry::class;

        return $services;
    }
}
