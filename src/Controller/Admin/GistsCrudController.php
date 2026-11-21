<?php

namespace App\Controller\Admin;

use App\Entity\Gist;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class GistsCrudController extends AbstractWapinetCrudController
{
    public static function getEntityFqcn(): string
    {
        return Gist::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['id', 'ip', 'browser', 'body', 'subject']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new')
            ->add('index', $this->openAction())
            ->add('detail', $this->openAction())
            ->add('index', $this->whoisAction())
            ->add('detail', $this->whoisAction());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user', 'Пользователь')->autocomplete())
            ->add(DateTimeFilter::new('createdAt', 'Дата создания'));
    }

    public function configureFields(string $pageName): iterable
    {
        $subject = TextField::new('subject', 'Тема');
        $body = TextareaField::new('body', 'Текст')
            ->setNumOfRows(20)
            ->setFormTypeOption('attr', ['data-bbcode-preview' => '1']);
        $id = IdField::new('id', 'ID');
        $ip = TextField::new('ip', 'IP');
        $browser = TextField::new('browser', 'Браузер');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt', 'Дата обновления')->formatValue(static fn (?\DateTimeImmutable $value): string => $value?->format('d.m.Y H:i') ?? '—');
        $user = AssociationField::new('user', 'Пользователь')
            ->formatValue(static fn (?User $value): string => $value?->getUsername() ?? '—');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$user, $subject, $ip, $createdAt];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $user, $subject, $ip, $browser, $createdAt, $updatedAt, $body];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$subject, $body];
        }

        return [];
    }

    protected function singularLabel(): string
    {
        return 'Запись блога';
    }

    protected function pluralLabel(): string
    {
        return 'Блоги';
    }

    private function openAction(): Action
    {
        return Action::new('open', 'Открыть', 'fas fa-arrow-up-right-from-square')
            ->linkToUrl(static fn (Gist $gist): string => '/gist/view/'.$gist->getId())
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);
    }

    private function whoisAction(): Action
    {
        return Action::new('whois', 'WHOIS', 'fas fa-magnifying-glass')
            ->linkToUrl(static fn (Gist $gist): string => '/whois?query='.\urlencode($gist->getIp()))
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);
    }
}
