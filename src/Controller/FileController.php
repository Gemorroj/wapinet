<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\FileTags;
use App\Entity\Tag;
use App\Entity\User;
use App\Exception\FileDuplicatedException;
use App\Form\Type\File\EditType;
use App\Form\Type\File\PasswordType;
use App\Form\Type\File\SearchType;
use App\Form\Type\File\UploadType;
use App\Message\FileAddMessage;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\Archiver\Archive7z;
use App\Service\BotChecker;
use App\Service\File\Meta;
use App\Service\Manticore;
use App\Service\MimeGuesser;
use App\Service\Paginate;
use App\Service\Timezone;
use App\Service\Translit;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Router;

#[Route('/file')]
class FileController extends AbstractController
{
    #[Route(path: '', name: 'file_index', options: ['expose' => true])]
    public function indexAction(): Response
    {
        return $this->render('File/index.html.twig');
    }

    #[Route(path: '/information', name: 'file_information')]
    public function informationAction(): Response
    {
        return $this->render('File/information.html.twig');
    }

    #[Route(path: '/statistic', name: 'file_statistic')]
    public function statisticAction(FileRepository $fileRepository): Response
    {
        $statistic = $fileRepository->getStatistic();

        return $this->render('File/statistic.html.twig', ['statistic' => $statistic]);
    }

    #[Route(path: '/search/{key}', name: 'file_search', requirements: ['key' => '[a-zA-Z0-9]+'], defaults: ['key' => null])]
    public function searchAction(Request $request, ?string $key = null): Response
    {
        $page = (int) $request->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid('', false);
                    $request->getSession()->set('file_search', [
                        'key' => $key,
                        'data' => $data,
                    ]);
                }

                return $this->redirectToRoute('file_search', ['key' => $key]);
            }

            if (null !== $key && $request->getSession()->has('file_search')) {
                $search = $request->getSession()->get('file_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchManticore($search['data'], $page);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('File/search.html.twig', [
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ]);
    }

    private function searchManticore(array $data, int $page = 1): Pagerfanta
    {
        /** @var Manticore $client */
        $client = $this->container->get(Manticore::class);

        if ('date' === $data['sort']) {
            $orderBy = 'created_at_ts';
        } else {
            $orderBy = 'WEIGHT()';
        }

        return $client->getPage(
            File::class,
            'files',
            ['original_file_name', 'description', 'tag_name'],
            $data['search'],
            $page,
            $orderBy,
        );
    }

    #[Route(path: '/categories', name: 'file_categories')]
    public function categoriesAction(): Response
    {
        return $this->render('File/categories.html.twig');
    }

    #[Route(path: '/hidden', name: 'file_hidden')]
    public function hiddenAction(Request $request, FileRepository $fileRepository, Paginate $paginate): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        $this->denyAccessUnlessGranted('ROLE_ADMIN', $user);

        $page = (int) $request->get('page', 1);

        $query = $fileRepository->getHiddenQuery();
        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('File/list.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route(path: '/tags', name: 'file_tags')]
    public function tagsAction(Request $request, TagRepository $tagRepository, Paginate $paginate): Response
    {
        $page = (int) $request->get('page', 1);
        $query = $tagRepository->getTagsQuery();

        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('File/tags.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route(path: '/tags/{tagName}', name: 'file_tag', requirements: ['tagName' => '.+'])]
    public function tagAction(Request $request, string $tagName, TagRepository $tagRepository, FileRepository $fileRepository, Paginate $paginate): Response
    {
        $page = (int) $request->get('page', 1);

        $tag = $tagRepository->getTagByName($tagName);
        if (null === $tag) {
            throw $this->createNotFoundException('Тэг не найден');
        }

        $query = $fileRepository->getTagFilesQuery($tag);

        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('File/tag.html.twig', [
            'pagerfanta' => $pagerfanta,
            'tag' => $tag,
        ]);
    }

    #[Route(path: '/users/{username}', name: 'file_user', requirements: ['username' => '.+'])]
    public function userAction(Request $request, string $username, UserRepository $userRepository, FileRepository $fileRepository, Paginate $paginate): Response
    {
        $page = (int) $request->get('page', 1);
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $query = $fileRepository->getUserFilesQuery($user);

        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('File/user.html.twig', [
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }

    #[Route(path: '/list/{date}/{category}', name: 'file_list', requirements: ['date' => 'all|today|yesterday', 'category' => 'video|audio|image|text|office|archive|android|java'], defaults: ['date' => 'all', 'category' => null])]
    public function listAction(Request $request, Timezone $timezoneHelper, FileRepository $fileRepository, Paginate $paginate, string $date = 'all', ?string $category = null): Response
    {
        $page = (int) $request->get('page', 1);

        $datetimeStart = null;
        $datetimeEnd = null;
        switch ($date) {
            case 'today':
                $datetimeStart = new \DateTime('today', $timezoneHelper->getTimezone());
                break;

            case 'yesterday':
                $datetimeStart = new \DateTime('yesterday', $timezoneHelper->getTimezone());
                $datetimeEnd = new \DateTime('today', $timezoneHelper->getTimezone());
                break;
        }

        $query = $fileRepository->getListQuery(
            $datetimeStart,
            $datetimeEnd,
            $category
        );
        $pagerfanta = $paginate->paginate($query, $page);

        return $this->render('File/list.html.twig', [
            'pagerfanta' => $pagerfanta,
            'date' => $date,
            'category' => $category,
        ]);
    }

    #[Route(path: '/{id}', name: 'file_view', requirements: ['id' => '\d+'])]
    public function viewAction(
        Request $request,
        File $file,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        Meta $fileMeta,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (null !== $file->getPassword() && !$this->isGranted('ROLE_ADMIN') && (!($user instanceof User) || !($file->getUser() instanceof User) || !$file->getUser()->isEqualTo($user))) {
            return $this->passwordAction($request, $file, $passwordHasherFactory, $fileMeta, $entityManager);
        }

        if ($file->isHidden()) {
            $isAdmin = ($user instanceof User) && $this->isGranted('ROLE_ADMIN', $user);
            $isFileUser = ($user instanceof User) && ($file->getUser() instanceof User) && $file->getUser()->isEqualTo($user);

            if (!$isAdmin && !$isFileUser) {
                throw $this->createNotFoundException('Файл скрыт и не доступен для просмотра');
            }
        }

        return $this->viewFile($file, $fileMeta, $entityManager);
    }

    private function viewFile(File $file, Meta $fileMeta, EntityManagerInterface $entityManager): Response
    {
        if (!$file->getMeta()) {
            try {
                $meta = $fileMeta->getFileMeta($file);
                $file->setMeta($meta);
            } catch (\Exception $e) {
                $this->container->get(LoggerInterface::class)->warning('Не удалось получить мета-информацию из файла.', [$e]);
            }
        }

        $lastViewAt = $file->getLastViewAt();
        $file->setCountViews($file->getCountViews() + 1);
        $file->setLastViewAt(new \DateTime());

        $entityManager->persist($file);
        $entityManager->flush();

        return $this->render('File/view.html.twig', ['file' => $file, 'lastViewAt' => $lastViewAt]);
    }

    public function passwordAction(
        Request $request,
        File $file,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        Meta $fileMeta,
        EntityManagerInterface $entityManager
    ): Response {
        $passwordHasher = $passwordHasherFactory->getPasswordHasher($file);
        $form = $this->createForm(PasswordType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    if (true !== $passwordHasher->verify($file->getPassword(), $data['password'])) {
                        throw $this->createAccessDeniedException('Неверный пароль');
                    }

                    return $this->viewFile($file, $fileMeta, $entityManager);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('File/password.html.twig', [
            'form' => $form->createView(),
            'id' => $file->getId(),
        ]);
    }

    #[Route(path: '/{id}/download/{name}', name: 'file_archive_download_file', requirements: ['id' => '\d+'])]
    public function archiveDownloadFileAction(Request $request, Filesystem $filesystem, FileRepository $fileRepository, int $id, string $name): BinaryFileResponse
    {
        $path = (string) \str_replace('\\', '/', $request->get('path', ''));
        if ('' === $path) {
            throw $this->createNotFoundException('Не указан файл для скачивания.');
        }

        $tmpDir = $this->getParameter('kernel.tmp_file_dir');
        $entry = $tmpDir.\DIRECTORY_SEPARATOR.$path;

        if (!$filesystem->exists($entry)) { // распаковываем архив
            /** @var File|null $file */
            $file = $fileRepository->find($id);
            if (!$file) {
                throw $this->createNotFoundException('Файл не найден.');
            }

            $archive = $this->container->get(Archive7z::class);

            try {
                $archive->extractEntry($file->getFile(), $path, $tmpDir);
            } catch (\Exception $e) {
                $filesystem->remove($entry); // 7zip создает пустой файл
                throw $this->createNotFoundException('Не удалось распаковать файл.');
            }

            $filesystem->chmod($entry, 0o644);
        }

        $file = new BinaryFileResponse(
            $this->container->get(\App\Service\File\File::class)->checkFile($tmpDir, $path, true)
        );

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->container->get(Translit::class)->toAscii($name)
        );

        return $file;
    }

    #[Route(path: '/accept/{id}', name: 'file_accept', requirements: ['id' => '\d+'])]
    public function acceptAction(File $file, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        $file->setHidden(false);

        $entityManager->persist($file);
        $entityManager->flush();

        // переадресация на файл
        $url = $this->generateUrl('file_view', ['id' => $file->getId()], Router::ABSOLUTE_URL);

        return $this->redirect($url);
    }

    #[Route(path: '/delete/{id}', name: 'file_delete', requirements: ['id' => '\d+'], options: ['expose' => true], methods: ['POST'])]
    public function deleteAction(Request $request, File $file, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('DELETE', $file) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        $entityManager->remove($file);

        // кэш
        $this->container->get(\App\Service\File\File::class)->cleanupFile($file);

        // сам файл и сброс в БД
        $entityManager->flush();

        // переадресация на главную
        $url = $this->generateUrl('file_index', [], Router::ABSOLUTE_URL);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['url' => $url]);
        }

        return $this->redirect($url);
    }

    #[Route(path: '/edit/{id}', name: 'file_edit', requirements: ['id' => '\d+'])]
    public function editAction(Request $request, File $file, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $file);

        $this->container->get(\App\Service\File\File::class)->copyFileTagsToTags($file);

        $oldFile = clone $file;
        $form = $this->createForm(EditType::class, $file);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->editFileData($request, $form->getData(), $oldFile, $entityManager);

                    $url = $this->generateUrl('file_view', ['id' => $file->getId()], Router::ABSOLUTE_URL);

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('File/edit.html.twig', [
            'form' => $form->createView(),
            'file' => $file,
        ]);
    }

    private function editFileData(Request $request, File $data, File $oldData, EntityManagerInterface $entityManager): File
    {
        /** @var UploadedFile|null $file */
        $file = $data->getFile();
        if (null !== $file) {
            $hash = \md5_file($file->getPathname());

            $existingFile = $entityManager->getRepository(File::class)->findOneBy(['hash' => $hash]);
            if (null !== $existingFile) {
                throw new FileDuplicatedException($existingFile, $this->container->get('router'));
            }

            $data->setHash($hash);
            $data->setOriginalFileName($file->getClientOriginalName());

            /** @var MimeGuesser $mimeGuesser */
            $mimeGuesser = $this->container->get(MimeGuesser::class);
            $data->setMimeType($mimeGuesser->getMimeType($file));
        }

        // обновляем ip и браузер только если файл редактирует владелец
        if ($data->getUser() && $data->getUser()->isEqualTo($this->getUser())) {
            // $data->setUser($this->getUser());
            $data->setIp($request->getClientIp());
            $data->setBrowser($request->headers->get('User-Agent', ''));
        }

        $data->setUpdatedAtValue();

        if (null !== $data->getPlainPassword()) {
            $this->container->get(\App\Service\File\File::class)->setPassword($data, $data->getPlainPassword());
            $data->setTags(new ArrayCollection());
        } else {
            $this->container->get(\App\Service\File\File::class)->removePassword($data);
        }
        $this->makeEditFileTags($data, $entityManager);

        $entityManager->persist($data);

        // если заменен файл
        if (null !== $file) {
            // чистим старый файл и кэш
            $this->container->get(\App\Service\File\File::class)->cleanupFile($oldData);
        }

        $entityManager->flush();

        return $data;
    }

    #[Route(path: '/upload', name: 'file_upload')]
    public function uploadAction(Request $request, PasswordHasherFactoryInterface $passwordHasherFactory, BotChecker $botChecker, EntityManagerInterface $entityManager, MessageBusInterface $messageBus): Response
    {
        $form = $this->createForm(UploadType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $botChecker->checkRequest($request);

                    $file = $this->saveFileData($request, $form->getData(), $passwordHasherFactory, $entityManager, $messageBus);

                    // просмотр файла авторизованными пользователями
                    if ($this->isGranted('ROLE_USER')) {
                        $url = $this->generateUrl(
                            'file_view',
                            [
                                'id' => $file->getId(),
                            ],
                            Router::ABSOLUTE_URL
                        );
                    } else {
                        // неавторизованных перенаправляем на главную обменника
                        $url = $this->generateUrl(
                            'file_index',
                            [],
                            Router::ABSOLUTE_URL
                        );
                    }

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('File/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function saveFileData(Request $request, File $file, PasswordHasherFactoryInterface $passwordHasherFactory, EntityManagerInterface $entityManager, MessageBusInterface $messageBus): File
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $file->getFile();

        $hash = \md5_file($uploadedFile->getPathname());

        $existingFile = $entityManager->getRepository(File::class)->findOneBy(['hash' => $hash]);
        if (null !== $existingFile) {
            throw new FileDuplicatedException($existingFile, $this->container->get('router'));
        }

        $file->setHash($hash);
        $file->setOriginalFileName($uploadedFile->getClientOriginalName());

        /** @var MimeGuesser $mimeGuesser */
        $mimeGuesser = $this->container->get(MimeGuesser::class);
        $file->setMimeType($mimeGuesser->getMimeType($uploadedFile));

        $file->setUser($this->getUser());
        $file->setIp($request->getClientIp());
        $file->setBrowser($request->headers->get('User-Agent', ''));

        if (null !== $file->getPlainPassword()) {
            $file->setFileTags(new ArrayCollection()); // не задаем тэги для запароленых файлов

            $passwordHasher = $passwordHasherFactory->getPasswordHasher($file);
            $hashedPassword = $passwordHasher->hash($file->getPlainPassword());
            $file->setPassword($hashedPassword);

            // Запароленные файлы не скрываем
            $file->setHidden(false);
        }

        $this->makeFileTags($file);

        $entityManager->persist($file);

        $entityManager->flush();

        $messageBus->dispatch(new FileAddMessage($file->getId()));

        return $file;
    }

    /**
     * TODO: отрефакторить.
     */
    private function makeEditFileTags(File $file, EntityManagerInterface $entityManager): void
    {
        // удаляем из коллекции устаревшие тэги
        $removedFileTagsCollection = $file->getFileTags()->filter(static function (FileTags $oldFileTags) use ($file): bool {
            foreach ($file->getTags() as $newTag) {
                if ($newTag === $oldFileTags->getTag()) {
                    return false;
                }
            }

            return true;
        });

        foreach ($removedFileTagsCollection as $removedFileTags) {
            $file->getFileTags()->removeElement($removedFileTags);
            $entityManager->remove($removedFileTags);
        }

        // Находим добавленные тэги, которых не было в коллекции
        $newTagsCollection = $file->getTags()->filter(static function (Tag $newTag) use ($file): bool {
            foreach ($file->getFileTags() as $fileTags) {
                if ($newTag === $fileTags->getTag()) {
                    return false;
                }
            }

            return true;
        });

        foreach ($newTagsCollection as $newTag) {
            $file->getFileTags()->add(
                (new FileTags())->setTag($newTag)->setFile($file)
            );
        }
    }

    private function makeFileTags(File $file): void
    {
        $tags = $file->getTags();

        $fileTags = new ArrayCollection();
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $fileTags->add(
                (new FileTags())->setTag($tag)->setFile($file)
            );
        }

        $file->setFileTags($fileTags);
    }

    #[Route(path: '/tags_search', name: 'file_tags_search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function tagsSearchAction(Request $request, TagRepository $tagRepository): JsonResponse
    {
        $term = \trim($request->get('term', ''));
        if ('' === $term) {
            return $this->json([]);
        }

        $exTerm = \explode(',', $term);
        $term = \ltrim(\end($exTerm));
        if ('' === $term) {
            return $this->json([]);
        }

        $tags = $tagRepository->findLikeName($term);

        $result = [];
        foreach ($tags as $tag) {
            $result[] = $tag->getName();
        }

        return $this->json($result);
    }

    #[Route(path: '/swiper/{id}', name: 'file_swiper', requirements: ['id' => '\d+'])]
    public function swiperAction(File $file, FileRepository $fileRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$file->isImage()) {
            throw new \InvalidArgumentException('Просмотр возможен только для картинок.');
        }

        if (null !== $file->getPassword()) {
            throw new \InvalidArgumentException('Просмотр файлов защищенных паролем не поддерживается.');
        }

        if ($file->isHidden()) {
            throw new \InvalidArgumentException('Файл скрыт и не доступен для просмотра.');
        }

        $prevFile = $fileRepository->getPrevFile($file->getId(), 'image');
        $nextFile = $fileRepository->getNextFile($file->getId(), 'image');

        $lastViewAt = $file->getLastViewAt();
        $file->setCountViews($file->getCountViews() + 1);
        $file->setLastViewAt(new \DateTime());

        $entityManager->persist($file);
        $entityManager->flush();

        return $this->render('File/swiper.html.twig', [
            'file' => $file,
            'lastViewAt' => $lastViewAt,
            'prevFile' => $prevFile,
            'nextFile' => $nextFile,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Translit::class] = '?'.Translit::class;
        $services[Manticore::class] = '?'.Manticore::class;
        $services[LoggerInterface::class] = '?'.LoggerInterface::class;
        $services[Archive7z::class] = '?'.Archive7z::class;
        $services[\App\Service\File\File::class] = '?'.\App\Service\File\File::class;
        $services[Paginate::class] = Paginate::class;
        $services[MimeGuesser::class] = MimeGuesser::class;

        return $services;
    }
}
