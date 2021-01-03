<?php

namespace App\Controller\Admin;

use App\Entity\Gist;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GistsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Gist::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'ip', 'browser', 'body', 'subject']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFields(string $pageName): iterable
    {
        $subject = TextField::new('subject', 'Тема');
        $body = TextareaField::new('body');
        $id = IdField::new('id', 'ID');
        $ip = TextField::new('ip');
        $browser = TextField::new('browser');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt');
        $user = AssociationField::new('user', 'Пользователь');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$user, $createdAt, $subject];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $ip, $browser, $createdAt, $updatedAt, $body, $subject, $user];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$subject, $body];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$subject, $body];
        }
    }
}
