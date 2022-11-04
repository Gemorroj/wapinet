<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class UsersCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'username', 'email', 'roles', 'sex', 'info', 'timezone', 'country', 'vk'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFields(string $pageName): iterable
    {
        $username = TextField::new('username', 'Логин');
        $enabled = BooleanField::new('enabled')->setRequired(false);
        $email = EmailField::new('email');
        $roles = ChoiceField::new('roles', 'Роли')
            ->allowMultipleChoices(true)
            ->renderExpanded(true)
            ->setChoices([
                'Админ' => 'ROLE_SUPER_ADMIN',
                'Модератор' => 'ROLE_ADMIN',
                'Пользователь' => 'ROLE_USER',
            ]);

        $sex = ChoiceField::new('sex')
            ->setRequired(false)
            ->setChoices([
                'Мужской' => User::SEX_MALE,
                'Женский' => User::SEX_FEMALE,
            ]);

        $birthday = DateField::new('birthday')->setRequired(false);
        $timezone = TimezoneField::new('timezone')->setRequired(false);
        $info = TextareaField::new('info')->setRequired(false);
        $id = IdField::new('id', 'ID');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt');
        $salt = TextField::new('salt');
        $password = TextField::new('password');
        $lastActivity = DateTimeField::new('lastActivity', 'Дата последней активности');
        $country = CountryField::new('country');
        $vk = UrlField::new('vk');
        $panel = AssociationField::new('panel');
        $subscriber = AssociationField::new('subscriber');
        $friends = AssociationField::new('friends');
        $friended = AssociationField::new('friended');
        $avatar = AvatarField::new('email', 'Аватар')
            ->setIsGravatarEmail(true)
            ->setHeight(48);

        if (Crud::PAGE_INDEX === $pageName) {
            return [$username, $avatar, $email, $roles, $createdAt, $lastActivity];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $enabled, $createdAt, $updatedAt, $username, $email, $salt, $password, $roles, $lastActivity, $sex, $birthday, $info, $timezone, $country, $vk, $panel, $subscriber, $friends, $friended];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$username, $enabled, $email, $roles, $sex, $birthday, $timezone, $info];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$username, $enabled, $email, $roles, $sex, $birthday, $timezone, $info];
        }

        return [];
    }
}
