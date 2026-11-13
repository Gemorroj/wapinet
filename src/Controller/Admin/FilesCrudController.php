<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\User;
use App\Service\File\FileManager;
use App\Twig\Extension\Size;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
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
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityUpdater;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FilesCrudController extends AbstractWapinetCrudController
{
    public function __construct(
        private readonly FileManager $fileManager,
        private readonly EntityUpdater $entityUpdater,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Size $sizeFormatter,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return File::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setSearchFields(['id', 'ip', 'browser', 'countViews', 'mimeType', 'fileSize', 'fileName', 'originalFileName', 'description', 'hash']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new')
            ->add('index', $this->openAction())
            ->add('detail', $this->openAction())
            ->add('index', $this->downloadAction())
            ->add('detail', $this->downloadAction());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('originalFileName', 'Имя файла'))
            ->add(EntityFilter::new('user', 'Пользователь')->autocomplete())
            ->add(BooleanFilter::new('hidden', 'Скрыт'))
            ->add(DateTimeFilter::new('createdAt', 'Дата создания'))
            ->add(NumericFilter::new('fileSize', 'Размер, байт'));
    }

    public function configureFields(string $pageName): iterable
    {
        $originalFileName = TextField::new('originalFileName', 'Имя файла');
        $mimeType = TextField::new('mimeType', 'MIME тип');
        $description = TextareaField::new('description', 'Описание')
            ->setNumOfRows(4)
            ->setFormTypeOption('attr', ['data-bbcode-preview' => '1']);
        $countViews = IntegerField::new('countViews', 'Просмотров')->setTextAlign('right');
        $hidden = BooleanField::new('hidden', 'Скрыт')->setRequired(false);
        $id = IdField::new('id', 'ID');
        $ip = TextField::new('ip', 'IP');
        $browser = TextField::new('browser', 'Браузер');
        $createdAt = DateTimeField::new('createdAt', 'Дата создания');
        $updatedAt = DateTimeField::new('updatedAt', 'Дата обновления')
            ->formatValue(static fn (?\DateTimeImmutable $value): string => $value?->format('d.m.Y H:i') ?? '—');
        $lastViewAt = DateTimeField::new('lastViewAt', 'Последний просмотр')
            ->formatValue(static fn (?\DateTimeImmutable $value): string => $value?->format('d.m.Y H:i') ?? '—');
        $password = TextField::new('password', 'Пароль')->formatValue(static fn (?string $value): string => null !== $value ? 'Да' : 'Нет');
        $fileSize = IntegerField::new('fileSize', 'Размер')
            ->formatValue(fn (?int $value): string => $this->sizeFormatter->getSize($value))
            ->setTextAlign('right');
        $fileName = TextField::new('fileName', 'Имя в хранилище');
        $hash = TextField::new('hash', 'MD5');
        $meta = TextField::new('meta', 'Метаданные');
        $fileTags = AssociationField::new('fileTags', 'Теги');
        $user = AssociationField::new('user', 'Пользователь')->formatValue(static fn (?User $value): string => $value?->getUsername() ?? '—');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$originalFileName, $createdAt, $user, $mimeType, $fileSize, $description, $countViews, $password, $hidden];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $originalFileName, $user, $mimeType, $fileSize, $createdAt, $updatedAt, $lastViewAt, $countViews, $password, $hidden, $ip, $browser, $description, $fileName, $hash, $meta, $fileTags];
        }
        if (Crud::PAGE_NEW === $pageName) {
            return [$originalFileName, $mimeType, $description, $countViews];
        }
        if (Crud::PAGE_EDIT === $pageName) {
            return [$originalFileName, $mimeType, $description, $countViews];
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

        $this->entityUpdater->updateProperty($entityDto, $propertyName, $newValue);

        $event = new BeforeEntityUpdatedEvent($entityDto->getInstance());
        $this->eventDispatcher->dispatch($event);
        $entityInstance = $event->getEntityInstance();

        $this->updateEntity($this->entityManager, $entityInstance);

        $this->eventDispatcher->dispatch(new AfterEntityUpdatedEvent($entityInstance));

        $entityDto->setInstance($entityInstance);

        $parameters = KeyValueStore::new([
            'action' => Action::EDIT,
            'entity' => $entityDto,
        ]);

        $event = new AfterCrudActionEvent($this->getContext(), $parameters);
        $this->eventDispatcher->dispatch($event);

        return $event;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if (!$entityInstance instanceof File) {
            throw new \InvalidArgumentException('Expected '.File::class.'.');
        }

        $this->fileManager->cleanupFile($entityInstance);

        $entityManager->remove($entityInstance);
        $entityManager->flush();
    }

    protected function singularLabel(): string
    {
        return 'Файл';
    }

    protected function pluralLabel(): string
    {
        return 'Файлы';
    }

    private function openAction(): Action
    {
        return Action::new('open', 'Открыть', 'fas fa-arrow-up-right-from-square')
            ->linkToUrl(static fn (File $file): string => '/files/'.$file->getId())
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);
    }

    private function downloadAction(): Action
    {
        return Action::new('download', 'Скачать', 'fas fa-download')
            ->linkToCrudAction('download');
    }

    #[AdminRoute('/{id}/download', name: 'download', options: ['methods' => ['GET']])]
    public function download(#[MapEntity] File $file): BinaryFileResponse
    {
        $path = $file->getFile()?->getRealPath();

        if (false === $path || null === $path || !\is_file($path)) {
            throw new NotFoundHttpException('Файл отсутствует на диске.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getOriginalFileName());
        $response->headers->set('Content-Type', '' !== $file->getMimeType() ? $file->getMimeType() : 'application/octet-stream');

        return $response;
    }
}
