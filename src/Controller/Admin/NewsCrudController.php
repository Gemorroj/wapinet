<?php

namespace App\Controller\Admin;

use App\Entity\News;
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

class NewsCrudController extends AbstractWapinetCrudController
{
    public static function getEntityFqcn(): string
    {
        return News::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['id', 'subject', 'body']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add('index', $this->openAction())
            ->add('detail', $this->openAction());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('createdAt', 'Дата создания'));
    }

    public function configureFields(string $pageName): iterable
    {
        $subject = TextField::new('subject', 'Заголовок');
        $body = TextareaField::new('body', 'Текст')
            ->setNumOfRows(20)
            ->setFormTypeOption('attr', ['data-bbcode-preview' => '1']);
        $id = IdField::new('id', 'ID');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt', 'Дата обновления')->formatValue(static fn (?\DateTimeImmutable $value): string => $value?->format('d.m.Y H:i') ?? '—');
        $createdBy = AssociationField::new('createdBy', 'Автор')
            ->formatValue(static fn (?User $value): string => $value?->getUsername() ?? '—');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$subject, $createdBy, $createdAt, $updatedAt];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $subject, $createdBy, $createdAt, $updatedAt, $body];
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
        $user = $this->getUser();

        if ($user instanceof User) {
            $news->setCreatedBy($user);
        }

        return $news;
    }

    protected function singularLabel(): string
    {
        return 'Новость';
    }

    protected function pluralLabel(): string
    {
        return 'Новости';
    }

    private function openAction(): Action
    {
        return Action::new('open', 'Открыть', 'fas fa-arrow-up-right-from-square')
            ->linkToUrl(static fn (News $news): string => '/news/'.$news->getId())
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);
    }
}
