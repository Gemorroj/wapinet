<?php

namespace App\Controller\Admin;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterCrudActionEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FilesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return File::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setSearchFields(['id', 'ip', 'browser', 'countViews', 'mimeType', 'fileSize', 'fileName', 'originalFileName', 'description', 'hash'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('originalFileName')
            ->add(EntityFilter::new('user'))
            ->add('hidden');
    }

    public function configureFields(string $pageName): iterable
    {
        $originalFileName = TextField::new('originalFileName', 'Имя файла');
        $mimeType = TextField::new('mimeType', 'MIME тип');
        $description = TextareaField::new('description', 'Описание');
        $countViews = IntegerField::new('countViews', 'Просмотров');
        $hidden = BooleanField::new('hidden', 'Скрыт')->setRequired(false);
        $id = IdField::new('id', 'ID');
        $ip = TextField::new('ip');
        $browser = TextField::new('browser');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt');
        $lastViewAt = DateTimeField::new('lastViewAt');
        $salt = TextField::new('salt');
        $password = BooleanField::new('password', 'Пароль');
        $fileSize = IntegerField::new('fileSize', 'Размер');
        $fileName = TextField::new('fileName');
        $hash = TextField::new('hash');
        $meta = TextField::new('meta')->setRequired(false);
        $fileTags = AssociationField::new('fileTags');
        $user = AssociationField::new('user', 'Пользователь');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$originalFileName, $createdAt, $user, $mimeType, $fileSize, $description, $countViews, $password, $hidden];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $ip, $browser, $createdAt, $updatedAt, $lastViewAt, $countViews, $salt, $password, $mimeType, $fileSize, $fileName, $originalFileName, $description, $hash, $meta, $hidden, $fileTags, $user];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$originalFileName, $mimeType, $description, $countViews, $hidden];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$originalFileName, $mimeType, $description, $countViews, $hidden];
        }

        return [];
    }

    /**
     * replace false fo null.
     */
    protected function ajaxEdit(EntityDto $entityDto, ?string $propertyName, bool $newValue): AfterCrudActionEvent
    {
        if ('password' === $propertyName) {
            if ($newValue) {
                throw new BadRequestHttpException();
            }
            $newValue = null;
        }

        $this->container->get(EntityUpdater::class)->updateProperty($entityDto, $propertyName, $newValue);

        $event = new BeforeEntityUpdatedEvent($entityDto->getInstance());
        $this->container->get('event_dispatcher')->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        $this->updateEntity($this->container->get('doctrine')->getManagerForClass($entityDto->getFqcn()), $entityInstance);

        $this->container->get('event_dispatcher')->dispatch(new AfterEntityUpdatedEvent($entityInstance));

        $entityDto->setInstance($entityInstance);

        $parameters = KeyValueStore::new([
            'action' => Action::EDIT,
            'entity' => $entityDto,
        ]);

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->container->get('event_dispatcher')->dispatch($event);

        return $event;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->container->get(\App\Service\File\File::class)->cleanupFile($entityInstance);

        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            \App\Service\File\File::class,
        ];
    }
}
