<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\FileTags;
use App\Entity\Tag;
use App\Entity\User;
use App\Event\FileEvent;
use App\Exception\FileDuplicatedException;
use App\Form\Type\File\EditType;
use App\Form\Type\File\PasswordType;
use App\Form\Type\File\SearchType;
use App\Form\Type\File\UploadType;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Service\Archiver\Archive7z;
use App\Service\BotChecker;
use App\Service\File\Meta;
use App\Service\Manticore;
use App\Service\Paginate;
use App\Service\Timezone;
use App\Service\Translit;
use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;

/**
 * @Route("/file")
 *
 * @see http://wap4file.org
 */
class FileController extends AbstractController
{
    /**
     * @Route("", name="file_index", options={"expose": true})
     */
    public function indexAction(): Response
    {
        return $this->render('File/index.html.twig');
    }

    /**
     * @Route("/information", name="file_information")
     */
    public function informationAction(): Response
    {
        return $this->render('File/information.html.twig');
    }

    /**
     * @Route("/statistic", name="file_statistic")
     */
    public function statisticAction(): Response
    {
        /** @var FileRepository $repository */
        $repository = $this->getDoctrine()->getRepository(File::class);
        $statistic = $repository->getStatistic();

        return $this->render('File/statistic.html.twig', ['statistic' => $statistic]);
    }

    /**
     * @Route("/search/{key}", name="file_search", defaults={"key": null}, requirements={"key": "[a-zA-Z0-9]+"})
     */
    public function searchAction(Request $request, SessionInterface $session, ?string $key = null): Response
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid('', false);
                    $session->set('file_search', [
                        'key' => $key,
                        'data' => $data,
                    ]);
                }

                return $this->redirectToRoute('file_search', ['key' => $key]);
            }

            if (null !== $key && $session->has('file_search')) {
                $search = $session->get('file_search');
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

    protected function searchManticore(array $data, int $page = 1): Pagerfanta
    {
        /** @var Manticore $client */
        $client = $this->get(Manticore::class);

        $sphinxQl = $client->select($page)
            ->from(['files'])
            ->match(['original_file_name', 'description', 'tag_name'], $data['search'])
        ;

        if ('date' === $data['sort']) {
            $sphinxQl->orderBy('created_at_ts', 'desc');
        } else {
            $sphinxQl->orderBy('WEIGHT()', 'desc');
        }

        return $client->getPagerfanta($sphinxQl, File::class);
    }

    /**
     * @Route("/categories", name="file_categories")
     */
    public function categoriesAction(): Response
    {
        return $this->render('File/categories.html.twig');
    }

    /**
     * @Route("/hidden", name="file_hidden")
     */
    public function hiddenAction(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            $this->createAccessDeniedException();
        }
        $this->denyAccessUnlessGranted('ROLE_ADMIN', $user);

        $page = $request->get('page', 1);

        /** @var FileRepository $repository */
        $repository = $this->getDoctrine()->getRepository(File::class);
        $query = $repository->getHiddenQuery();
        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('File/list.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    /**
     * @Route("/tags", name="file_tags")
     */
    public function tagsAction(Request $request): Response
    {
        $page = $request->get('page', 1);
        /** @var TagRepository $tagRepository */
        $tagRepository = $this->getDoctrine()->getRepository(Tag::class);
        $query = $tagRepository->getTagsQuery();

        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('File/tags.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    /**
     * @Route("/tags/{tagName}", name="file_tag", requirements={"tagName": ".+"})
     */
    public function tagAction(Request $request, string $tagName): Response
    {
        $page = $request->get('page', 1);
        /** @var TagRepository $tagRepository */
        $tagRepository = $this->getDoctrine()->getRepository(Tag::class);

        $tag = $tagRepository->getTagByName($tagName);
        if (null === $tag) {
            throw $this->createNotFoundException('Тэг не найден');
        }

        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getDoctrine()->getRepository(File::class);
        $query = $fileRepository->getTagFilesQuery($tag);

        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('File/tag.html.twig', [
            'pagerfanta' => $pagerfanta,
            'tag' => $tag,
        ]);
    }

    /**
     * @Route("/users/{username}", name="file_user", requirements={"username": ".+"})
     */
    public function userAction(Request $request, string $username): Response
    {
        $page = $request->get('page', 1);
        /** @var User|null $user */
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $username]);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getDoctrine()->getRepository(File::class);
        $query = $fileRepository->getUserFilesQuery($user);

        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('File/user.html.twig', [
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ]);
    }

    /**
     * @Route("/list/{date}/{category}", name="file_list", defaults={"date": "all", "category": null}, requirements={"date": "all|today|yesterday", "category": "video|audio|image|text|office|archive|android|java"})
     */
    public function listAction(Request $request, Timezone $timezoneHelper, string $date = 'all', ?string $category = null): Response
    {
        $page = $request->get('page', 1);

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

        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getDoctrine()->getRepository(File::class);
        $query = $fileRepository->getListQuery(
            $datetimeStart,
            $datetimeEnd,
            $category
        );
        $pagerfanta = $this->get(Paginate::class)->paginate($query, $page);

        return $this->render('File/list.html.twig', [
            'pagerfanta' => $pagerfanta,
            'date' => $date,
            'category' => $category,
        ]);
    }

    /**
     * @Route("/{id}", name="file_view", requirements={"id": "\d+"})
     */
    public function viewAction(File $file, PasswordHasherFactoryInterface $passwordHasherFactory, Meta $fileMeta): Response
    {
        if (null !== $file->getPassword() && !$this->isGranted('ROLE_ADMIN') && (!($this->getUser() instanceof User) || !($file->getUser() instanceof User) || !$file->getUser()->isEqualTo($this->getUser()))) {
            return $this->passwordAction($file, $passwordHasherFactory, $fileMeta);
        }

        if ($file->isHidden()) {
            $isAdmin = ($this->getUser() instanceof User) && ($this->isGranted('ROLE_ADMIN', $this->getUser()));
            $isFileUser = ($this->getUser() instanceof User) && ($file->getUser() instanceof User) && ($file->getUser()->isEqualTo($this->getUser()));

            if (!$isAdmin && !$isFileUser) {
                throw $this->createNotFoundException('Файл скрыт и не доступен для просмотра');
            }
        }

        return $this->viewFile($file, $fileMeta);
    }

    protected function incrementViews(File $file): void
    {
        $file->setCountViews($file->getCountViews() + 1);
        $file->setLastViewAt(new \DateTime());
    }

    protected function viewFile(File $file, Meta $fileMeta): Response
    {
        $this->checkMeta($file, $fileMeta);

        $response = $this->render('File/view.html.twig', ['file' => $file]);
        $this->incrementViews($file);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($file);
        $entityManager->flush();

        return $response;
    }

    protected function checkMeta(File $file, Meta $fileMeta): void
    {
        if (null !== $file->getMeta()) {
            return;
        }

        $meta = null;
        try {
            $meta = $fileMeta->setFile($file)->getFileMeta();
        } catch (\Exception $e) {
            $this->get(LoggerInterface::class)->warning('Не удалось получить мета-информацию из файла.', [$e]);
        }

        $file->setMeta($meta);
    }

    public function passwordAction(File $file, PasswordHasherFactoryInterface $passwordHasherFactory, Meta $fileMeta): Response
    {
        $passwordHasher = $passwordHasherFactory->getPasswordHasher($file);
        $form = $this->createForm(PasswordType::class);
        $request = $this->get('request_stack')->getCurrentRequest();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    if (true !== $passwordHasher->verify($file->getPassword(), $data['password'])) {
                        throw $this->createAccessDeniedException('Неверный пароль');
                    }

                    return $this->viewFile($file, $fileMeta);
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

    /**
     * @Route("/{id}/download/{name}", name="file_archive_download_file", requirements={"id": "\d+"})
     */
    public function archiveDownloadFileAction(Request $request, Filesystem $filesystem, int $id, string $name): BinaryFileResponse
    {
        $path = (string) \str_replace('\\', '/', $request->get('path', ''));
        if ('' === $path) {
            throw $this->createNotFoundException('Не указан файл для скачивания.');
        }

        $tmpDir = $this->getParameter('kernel.tmp_file_dir');
        $entry = $tmpDir.\DIRECTORY_SEPARATOR.$path;

        if (!$filesystem->exists($entry)) { // распаковываем архив
            /** @var File|null $file */
            $file = $this->getDoctrine()->getRepository(File::class)->find($id);
            if (null === $file) {
                throw $this->createNotFoundException('Файл не найден.');
            }

            $archive = $this->get(Archive7z::class);

            $archive->extractEntry($file->getFile(), $path, $tmpDir);
            if (!$filesystem->exists($entry)) {
                throw $this->createNotFoundException('Файл не найден.');
            }
            $filesystem->chmod($entry, 0644);
        }

        $file = new BinaryFileResponse(
            $this->get(\App\Service\File\File::class)->checkFile($tmpDir, $path, true)
        );

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->get(Translit::class)->toAscii($name)
        );

        return $file;
    }

    /**
     * @Route("/accept/{id}", name="file_accept", requirements={"id": "\d+"})
     */
    public function acceptAction(File $file): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        $file->setHidden(false);
        // БД
        $em = $this->getDoctrine()->getManager();
        $em->persist($file);
        $em->flush();

        // переадресация на файл
        $url = $this->generateUrl('file_view', ['id' => $file->getId()], Router::ABSOLUTE_URL);

        return $this->redirect($url);
    }

    /**
     * @Route("/delete/{id}", name="file_delete", methods={"POST"}, requirements={"id": "\d+"}, options={"expose": true})
     */
    public function deleteAction(Request $request, File $file): Response
    {
        if (!$this->isGranted('DELETE', $file) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        // БД
        $em = $this->getDoctrine()->getManager();
        $em->remove($file);

        // кэш
        $this->get(\App\Service\File\File::class)->cleanupFile($file);

        // сам файл и сброс в БД
        $em->flush();

        // переадресация на главную
        $url = $this->generateUrl('file_index', [], Router::ABSOLUTE_URL);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['url' => $url]);
        }

        return $this->redirect($url);
    }

    /**
     * @Route("/edit/{id}", name="file_edit", requirements={"id": "\d+"})
     */
    public function editAction(Request $request, File $file): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $file);

        $this->get(\App\Service\File\File::class)->copyFileTagsToTags($file);

        $oldFile = clone $file;
        $form = $this->createForm(EditType::class, $file);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->editFileData($request, $form->getData(), $oldFile);

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

    protected function editFileData(Request $request, File $data, File $oldData): File
    {
        /** @var UploadedFile|null $file */
        $file = $data->getFile();
        if (null !== $file) {
            $hash = \md5_file($file->getPathname());

            $existingFile = $this->getDoctrine()->getRepository(File::class)->findOneBy(['hash' => $hash]);
            if (null !== $existingFile) {
                throw new FileDuplicatedException($existingFile, $this->get('router'));
            }

            $data->setHash($hash);
            $data->setOriginalFileName($file->getClientOriginalName());

            /*
            $mimes = (new MimeTypes())->getMimeTypes($file->getClientOriginalExtension());
            $data->setMimeType($mimes ? $mimes[0] : 'application/octet-stream');
            */
            $data->setMimeType((new MimeTypes())->guessMimeType($file->getPathname()) ?: 'application/octet-stream');
        }

        // обновляем ip и браузер только если файл редактирует владелец
        if ($data->getUser() && $data->getUser()->isEqualTo($this->getUser())) {
            //$data->setUser($this->getUser());
            $data->setIp($request->getClientIp());
            $data->setBrowser($request->headers->get('User-Agent', ''));
        }

        $data->setUpdatedAtValue();

        if (null !== $data->getPlainPassword()) {
            $this->get(\App\Service\File\File::class)->setPassword($data, $data->getPlainPassword());
            $data->setTags(new ArrayCollection());
        } else {
            $this->get(\App\Service\File\File::class)->removePassword($data);
        }
        $this->makeEditFileTags($data);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($data);

        // если заменен файл
        if (null !== $file) {
            // чистим старый файл и кэш
            $this->get(\App\Service\File\File::class)->cleanupFile($oldData);
        }

        $entityManager->flush();

        return $data;
    }

    /**
     * @Route("/upload", name="file_upload")
     */
    public function uploadAction(Request $request, PasswordHasherFactoryInterface $passwordHasherFactory, BotChecker $botChecker): Response
    {
        $form = $this->createForm(UploadType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $botChecker->checkRequest($request);

                    $file = $this->saveFileData($request, $form->getData(), $passwordHasherFactory);

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

    protected function saveFileData(Request $request, File $data, PasswordHasherFactoryInterface $passwordHasherFactory): File
    {
        /** @var UploadedFile $file */
        $file = $data->getFile();

        $hash = \md5_file($file->getPathname());

        $existingFile = $this->getDoctrine()->getRepository(File::class)->findOneBy(['hash' => $hash]);
        if (null !== $existingFile) {
            throw new FileDuplicatedException($existingFile, $this->get('router'));
        }

        $data->setHash($hash);
        $data->setOriginalFileName($file->getClientOriginalName());

        /*
        $mimes = (new MimeTypes())->getMimeTypes($file->getClientOriginalExtension());
        $data->setMimeType($mimes ? $mimes[0] : 'application/octet-stream');
        */
        $data->setMimeType((new MimeTypes())->guessMimeType($file->getPathname()) ?: 'application/octet-stream');

        $data->setUser($this->getUser());
        $data->setIp($request->getClientIp());
        $data->setBrowser($request->headers->get('User-Agent', ''));

        if (null !== $data->getPlainPassword()) {
            $data->setFileTags(new ArrayCollection()); // не задаем тэги для запароленых файлов

            $passwordHasher = $passwordHasherFactory->getPasswordHasher($data);
            $hashedPassword = $passwordHasher->hash($data->getPlainPassword());
            $data->setPassword($hashedPassword);

            // Запароленные файлы не скрываем
            $data->setHidden(false);
        }

        $this->makeFileTags($data);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($data);

        $entityManager->flush();

        $this->get(EventDispatcherInterface::class)->dispatch(
            new FileEvent($data->getUser(), $data),
            FileEvent::FILE_ADD
        );

        return $data;
    }

    /**
     * TODO: отрефакторить.
     */
    private function makeEditFileTags(File $file): void
    {
        $manager = $this->getDoctrine()->getManager();

        // удаляем из коллекции устаревшие тэги
        $removedFileTagsCollection = $file->getFileTags()->filter(static function (FileTags $oldFileTags) use ($file) {
            foreach ($file->getTags() as $newTag) {
                if ($newTag === $oldFileTags->getTag()) {
                    return false;
                }
            }

            return true;
        });

        foreach ($removedFileTagsCollection as $removedFileTags) {
            $file->getFileTags()->removeElement($removedFileTags);
            $manager->remove($removedFileTags);
        }

        // Находим добавленные тэги, которых не было в коллекции
        $newTagsCollection = $file->getTags()->filter(static function (Tag $newTag) use ($file) {
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

    /**
     * @Route("/tags_search", name="file_tags_search", defaults={"_format": "json"}, options={"expose": true})
     */
    public function tagsSearchAction(Request $request): JsonResponse
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

        /** @var TagRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Tag::class);
        $tags = $repository->findLikeName($term);

        $result = [];
        foreach ($tags as $tag) {
            $result[] = $tag->getName();
        }

        return $this->json($result);
    }

    /**
     * @Route("/swiper/{id}", name="file_swiper", requirements={"id": "\d+"})
     */
    public function swiperAction(File $file): Response
    {
        /** @var FileRepository $repository */
        $repository = $this->getDoctrine()->getRepository(File::class);

        if (!$file->isImage()) {
            throw new \InvalidArgumentException('Просмотр возможен только для картинок.');
        }

        if (null !== $file->getPassword()) {
            throw new \InvalidArgumentException('Просмотр файлов защищенных паролем не поддерживается.');
        }

        if ($file->isHidden()) {
            throw new \InvalidArgumentException('Файл скрыт и не доступен для просмотра.');
        }

        $prevFile = $repository->getPrevFile($file->getId(), 'image');
        $nextFile = $repository->getNextFile($file->getId(), 'image');

        $response = $this->render('File/swiper.html.twig', [
            'file' => $file,
            'prevFile' => $prevFile,
            'nextFile' => $nextFile,
        ]);

        $this->incrementViews($file);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($file);
        $entityManager->flush();

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Translit::class] = '?'.Translit::class;
        $services[Manticore::class] = '?'.Manticore::class;
        $services[LoggerInterface::class] = '?'.LoggerInterface::class;
        $services[Archive7z::class] = '?'.Archive7z::class;
        $services[\App\Service\File\File::class] = '?'.\App\Service\File\File::class;
        $services[EventDispatcherInterface::class] = '?'.EventDispatcherInterface::class;
        $services[Paginate::class] = Paginate::class;

        return $services;
    }
}
