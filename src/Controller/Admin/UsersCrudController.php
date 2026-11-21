<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

class UsersCrudController extends AbstractWapinetCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['id', 'username', 'email', 'roles', 'sex', 'info', 'timezone', 'country', 'vk']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('enabled', 'Активен'))
            ->add(DateTimeFilter::new('createdAt', 'Дата регистрации'))
            ->add(DateTimeFilter::new('lastActivity', 'Последняя активность'));
    }

    public function configureFields(string $pageName): iterable
    {
        $username = TextField::new('username', 'Логин');
        $enabled = BooleanField::new('enabled', 'Активен')
            ->setRequired(false)
            ->setHelp('Отключённый пользователь не сможет войти на сайт.');
        $email = EmailField::new('email', 'Email');
        $roles = ChoiceField::new('roles', 'Роли')
            ->allowMultipleChoices(true)
            ->renderExpanded(true)
            ->renderAsBadges()
            ->setHelp('ROLE_SUPER_ADMIN — полный доступ, ROLE_ADMIN — модерация, ROLE_USER — обычный пользователь.')
            ->setChoices([
                'Админ' => 'ROLE_SUPER_ADMIN',
                'Модератор' => 'ROLE_ADMIN',
                'Пользователь' => 'ROLE_USER',
            ]);

        $sex = ChoiceField::new('sex', 'Пол')
            ->setRequired(false)
            ->setChoices([
                'Мужской' => User::SEX_MALE,
                'Женский' => User::SEX_FEMALE,
            ]);

        $birthday = DateField::new('birthday', 'Дата рождения')->setRequired(false);
        $timezone = TimezoneField::new('timezone', 'Часовой пояс')->setRequired(false);
        $info = TextareaField::new('info', 'О себе')
            ->setRequired(false)
            ->setNumOfRows(4)
            ->setFormTypeOption('attr', ['data-bbcode-preview' => '1']);
        $id = IdField::new('id', 'ID');
        $createdAt = DateTimeField::new('createdAt', 'Дата регистрации');
        $updatedAt = DateTimeField::new('updatedAt', 'Дата обновления');
        $lastActivity = DateTimeField::new('lastActivity', 'Последняя активность')
            ->formatValue(static fn (?\DateTimeImmutable $value): string => $value?->format('d.m.Y H:i') ?? '—');
        $country = CountryField::new('country', 'Страна');
        $vk = UrlField::new('vk', 'ВКонтакте');
        $panel = AssociationField::new('panel', 'Панель');
        $subscriber = AssociationField::new('subscriber', 'Подписки');
        $friends = AssociationField::new('friends', 'Друзья');
        $friended = AssociationField::new('friended', 'В друзьях у');
        $avatar = AvatarField::new('email', 'Аватар')
            ->setIsGravatarEmail(true)
            ->setHeight(48);

        if (Crud::PAGE_INDEX === $pageName) {
            return [$username, $avatar, $email, $roles, $enabled, $createdAt, $lastActivity];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $username, $enabled, $email, $roles, $createdAt, $updatedAt, $lastActivity, $sex, $birthday, $info, $timezone, $country, $vk, $panel, $subscriber, $friends, $friended];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [
                FormField::addFieldset('Аккаунт'),
                $username,
                $enabled,
                $email,
                $roles,
                FormField::addFieldset('Профиль'),
                $sex,
                $birthday,
                $timezone,
                $info,
            ];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [
                FormField::addFieldset('Аккаунт'),
                $username,
                $enabled,
                $email,
                $roles,
                FormField::addFieldset('Профиль'),
                $sex,
                $birthday,
                $timezone,
                $info,
            ];
        }

        return [];
    }

    protected function singularLabel(): string
    {
        return 'Пользователь';
    }

    protected function pluralLabel(): string
    {
        return 'Пользователи';
    }
}
