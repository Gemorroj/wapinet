<?php

namespace App\Controller\Admin;

use App\Entity\Subscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class SubscribersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscriber::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFields(string $pageName): iterable
    {
        $emailNews = Field::new('emailNews', 'Новости');
        $emailFriends = Field::new('emailFriends', 'События друзей');
        $id = IntegerField::new('id', 'ID');
        $user = AssociationField::new('user', 'Пользователь');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$user, $emailNews, $emailFriends];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $emailNews, $emailFriends, $user];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$emailNews, $emailFriends];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$emailNews, $emailFriends];
        }
    }
}
