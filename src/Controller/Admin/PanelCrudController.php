<?php

namespace App\Controller\Admin;

use App\Entity\Panel;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class PanelCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Panel::class;
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
        $forum = Field::new('forum', 'Форум');
        $guestbook = Field::new('guestbook', 'Гостевая');
        $gist = Field::new('gist', 'Блоги');
        $file = Field::new('file', 'Файлообменник');
        $archiver = Field::new('archiver', 'Архиватор');
        $downloads = Field::new('downloads', 'Развлечения');
        $utilities = Field::new('utilities', 'Утилиты');
        $programming = Field::new('programming', 'WEB мастерская');
        $id = IdField::new('id', 'ID');
        $user = AssociationField::new('user', 'Пользователь');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$user, $forum, $guestbook, $gist, $file, $archiver, $downloads, $utilities, $programming];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $forum, $guestbook, $gist, $file, $archiver, $downloads, $utilities, $programming, $user];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$forum, $guestbook, $gist, $file, $archiver, $downloads, $utilities, $programming];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$forum, $guestbook, $gist, $file, $archiver, $downloads, $utilities, $programming];
        }
    }
}
