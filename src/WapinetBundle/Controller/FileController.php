<?php

namespace WapinetBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use WapinetBundle\Entity\File;
use WapinetBundle\Entity\FileTags;
use WapinetBundle\Entity\Tag;
use WapinetBundle\Event\FileEvent;
use WapinetBundle\Exception\FileDuplicatedException;
use WapinetBundle\Form\Type\File\EditType;
use WapinetBundle\Form\Type\File\PasswordType;
use WapinetBundle\Form\Type\File\SearchType;
use WapinetBundle\Form\Type\File\UploadType;
use WapinetUserBundle\Entity\User;

/**
 * @see http://wap4file.org
 */
class FileController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('WapinetBundle:File:index.html.twig');
    }

    /**
     * @return Response
     */
    public function informationAction()
    {
        return $this->render('WapinetBundle:File:information.html.twig');
    }

    /**
     * @return Response
     */
    public function statisticAction()
    {
        $statistic = $this->getDoctrine()->getRepository('WapinetBundle:File')->getStatistic();

        return $this->render('WapinetBundle:File:statistic.html.twig', array('statistic' => $statistic));
    }

    /**
     * @param Request $request
     * @param string|null $key
     * @return Response|RedirectResponse
     */
    public function searchAction(Request $request, $key = null)
    {
        $page = $request->get('page', 1);
        $form = $this->createForm(SearchType::class);
        $pagerfanta = null;
        $session = $this->get('session');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid('', false);
                    $session->set('file_search', array(
                        'key' => $key,
                        'data' => $data
                    ));
                }

                return $this->redirectToRoute('file_search', array('key' => $key));
            }


            if (null !== $key && $session->has('file_search')) {
                $search = $session->get('file_search');
                if ($key === $search['key']) {
                    $form->setData($search['data']);
                    $pagerfanta = $this->searchSphinx($search['data'], $page);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:search.html.twig', array(
            'form' => $form->createView(),
            'pagerfanta' => $pagerfanta,
            'key' => $key,
        ));
    }


    /**
     * @param array $data
     * @param int   $page
     *
     * @throws \RuntimeException
     * @return Pagerfanta
     */
    protected function searchSphinx(array $data, $page = 1)
    {
        $client = $this->get('sphinx');
        $sphinxQl = $client->select($page)
            ->from('files')
            ->match(array('original_file_name', 'description', 'tag_name'), $data['search'])
        ;

        if ('date' === $data['sort']) {
            $sphinxQl->orderBy('created_at_ts', 'desc');
        } else {
            $sphinxQl->orderBy('WEIGHT()', 'desc');
        }

        return $client->getPagerfanta($sphinxQl, File::class);
    }


    /**
     * @return Response
     */
    public function categoriesAction()
    {
        return $this->render('WapinetBundle:File:categories.html.twig');
    }


    /**
     * @param Request $request
     *
     * @return Response
     */
    public function hiddenAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user || !$user->hasRole('ROLE_ADMIN') && !$user->hasRole('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        $page = $request->get('page', 1);

        $query = $this->getDoctrine()
            ->getRepository('WapinetBundle:File')
            ->getHiddenQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:list.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }


    /**
     * @param Request $request
     *
     * @return Response
     */
    public function tagsAction(Request $request)
    {
        $page = $request->get('page', 1);
        $tagManager = $this->getDoctrine()->getRepository('WapinetBundle:Tag');
        $query = $tagManager->getTagsQuery();

        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:tags.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }

    /**
     * @param Request $request
     * @param string $tagName
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function tagAction(Request $request, $tagName)
    {
        $page = $request->get('page', 1);
        $tagManager = $this->getDoctrine()->getRepository('WapinetBundle:Tag');

        $tag = $tagManager->getTagByName($tagName);
        if (null === $tag) {
            throw $this->createNotFoundException('Тэг не найден');
        }

        $fileManager = $this->getDoctrine()->getRepository('WapinetBundle:File');
        $query = $fileManager->getTagFilesQuery($tag);

        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:tag.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'tag' => $tag,
        ));
    }


    /**
     * @param Request $request
     * @param string $username
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function userAction(Request $request, $username)
    {
        $page = $request->get('page', 1);
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByUsername($username);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        $tagManager = $this->getDoctrine()->getRepository('WapinetBundle:File');

        $query = $tagManager->getUserFilesQuery($user);

        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:user.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'user' => $user,
        ));
    }

    /**
     * @param Request $request
     * @param string|null $date
     * @param string|null $category
     * @return Response
     */
    public function listAction(Request $request, $date = null, $category = null)
    {
        $page = $request->get('page', 1);

        $datetimeStart = null;
        $datetimeEnd = null;
        switch ($date) {
            case 'today':
                $datetimeStart = new \DateTime('today', $this->get('timezone')->getTimezone());
                break;

            case 'yesterday':
                $datetimeStart = new \DateTime('yesterday', $this->get('timezone')->getTimezone());
                $datetimeEnd = new \DateTime('today', $this->get('timezone')->getTimezone());
                break;
        }

        $query = $this->getDoctrine()
            ->getRepository('WapinetBundle:File')
            ->getListQuery(
                $datetimeStart,
                $datetimeEnd,
                $category
            );
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:File:list.html.twig', array(
            'pagerfanta' => $pagerfanta,
            'date' => $date,
            'category' => $category,
        ));
    }

    /**
     * @param File $file
     * @return Response
     */
    public function viewAction(File $file)
    {
        if (null !== $file->getPassword() && !$this->isGranted('ROLE_ADMIN') && (!($this->getUser() instanceof User) || !($file->getUser() instanceof User) || $file->getUser()->getId() !== $this->getUser()->getId())) {
            return $this->passwordAction($file);
        }


        if ($file->isHidden()) {
            $isAdmin = ($this->getUser() instanceof User) && ($this->getUser()->hasRole('ROLE_ADMIN') || $this->getUser()->hasRole('ROLE_SUPER_ADMIN'));
            $isFileUser = ($this->getUser() instanceof User) && ($file->getUser() instanceof User) && ($file->getUser()->getId() === $this->getUser()->getId());

            if (!$isAdmin && !$isFileUser) {
                throw $this->createNotFoundException('Файл скрыт и не доступен для просмотра');
            }
        }

        return $this->viewFile($file);
    }

    /**
     * @param File $file
     */
    protected function incrementViews(File $file)
    {
        $file->setCountViews($file->getCountViews() + 1);
        $file->setLastViewAt(new \DateTime());
    }

    /**
     * @param File $file
     *
     * @return Response
     */
    protected function viewFile(File $file)
    {
        $this->checkMeta($file);
        $response = $this->render('WapinetBundle:File:view.html.twig', array('file' => $file));
        $this->incrementViews($file);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($file);
        $entityManager->flush();

        return $response;
    }


    /**
     * @param File $file
     */
    protected function checkMeta(File $file)
    {
        if (null !== $file->getMeta()) {
            return;
        }

        $meta = null;
        try {
            $meta = $this->get('file_meta')->setFile($file)->getFileMeta();
        } catch (\Exception $e) {
            $this->get('logger')->warning('Не удалось получить мета-информацию из файла.', array($e));
        }

        $file->setMeta($meta);
    }

    /**
     * @param File $file
     *
     * @return Response
     */
    public function passwordAction(File $file)
    {
        $encoder = $this->get('security.encoder_factory')->getEncoder($file);
        $form = $this->createForm(PasswordType::class);
        $request = $this->get('request_stack')->getCurrentRequest();

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    if (true !== $encoder->isPasswordValid($file->getPassword(), $data['password'], $file->getSalt())) {
                        throw $this->createAccessDeniedException('Неверный пароль');
                    }

                    return $this->viewFile($file);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:password.html.twig', array(
            'form' => $form->createView(),
            'id' => $file->getId(),
        ));
    }

    /**
     * @param Request $request
     * @param int $id
     * @param string $name
     * @return BinaryFileResponse
     */
    public function archiveDownloadFileAction(Request $request, $id, $name)
    {
        $path = \str_replace('\\', '/', $request->get('path'));
        $tmpDir = $this->get('kernel')->getTmpFileDir();

        if (null === $path) {
            throw $this->createNotFoundException('Не указан файл для скачивания.');
        }

        $entry = $tmpDir . \DIRECTORY_SEPARATOR . $path;

        $filesystem = $this->get('filesystem');

        if (!$filesystem->exists($entry)) {
            $file = $this->getDoctrine()->getRepository('WapinetBundle:File')->find($id);
            if (null === $file) {
                throw $this->createNotFoundException('Файл не найден.');
            }

            $archive = $this->get('archive_7z');

            $archive->extractEntry($file->getFile(), $path, $tmpDir);
            $filesystem->chmod($entry, 0644);
        }

        $file = new BinaryFileResponse(
            $this->get('file')->checkFile($tmpDir, $path, true)
        );

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->get('translit')->toAscii($name)
        );

        return $file;
    }


    /**
     * @param Request $request
     * @param File $file
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|JsonResponse
     */
    public function acceptAction(Request $request, File $file)
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
        $url = $this->generateUrl('file_view', array('id' => $file->getId()), Router::ABSOLUTE_URL);

        return $this->redirect($url);
    }


    /**
     * @param Request $request
     * @param File $file
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|JsonResponse
     */
    public function deleteAction(Request $request, File $file)
    {
        if (!$this->isGranted('DELETE', $file) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        // БД
        $em = $this->getDoctrine()->getManager();
        $em->remove($file);

        // кэш
        $this->get('file')->cleanupFile($file);

        // сам файл и сброс в БД
        $em->flush();

        // переадресация на главную
        $url = $this->generateUrl('file_index', array(), Router::ABSOLUTE_URL);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(array('url' => $url));
        }

        return $this->redirect($url);
    }


    /**
     * @param Request $request
     * @param File $file
     *
     * @throws AccessDeniedException|NotFoundHttpException
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, File $file)
    {
        $this->denyAccessUnlessGranted('EDIT', $file);

        $this->get('file')->copyFileTagsToTags($file);

        $oldFile = clone $file;
        $form = $this->createForm(EditType::class, $file);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->editFileData($request, $form->getData(), $oldFile);

                    $url = $this->generateUrl('file_view', array('id' => $file->getId()), Router::ABSOLUTE_URL);

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:edit.html.twig', array(
            'form' => $form->createView(),
            'file' => $file,
        ));
    }


    /**
     * @param Request $request
     * @param File    $data
     * @param File    $oldData
     * @throws FileDuplicatedException
     * @return File
     */
    protected function editFileData(Request $request, File $data, File $oldData)
    {
        /** @var UploadedFile|null $file */
        $file = $data->getFile();
        if (null !== $file) {
            $hash = \md5_file($file->getPathname());

            $existingFile = $this->getDoctrine()->getRepository('WapinetBundle:File')->findOneBy(array('hash' => $hash));
            if (null !== $existingFile) {
                throw new FileDuplicatedException($existingFile, $this->container);
            }

            $data->setHash($hash);
            $data->setMimeType($this->get('mime')->getMimeType($file->getClientOriginalName()));
            $data->setFileSize($file->getSize());
            $data->setOriginalFileName($file->getClientOriginalName());
        }

        // обновляем ip и браузер только если файл редактирует владелец
        if ($data->getUser() && $data->getUser()->getId() === $this->getUser()->getId()) {
            //$data->setUser($this->getUser());
            $data->setIp($request->getClientIp());
            $data->setBrowser($request->headers->get('User-Agent', ''));
        }

        $data->setUpdatedAtValue();

        if (null !== $data->getPlainPassword()) {
            $this->get('file')->setPassword($data, $data->getPlainPassword());
            $data->setTags(new ArrayCollection());
        } else {
            $this->get('file')->removePassword($data);
        }
        $this->makeEditFileTags($data);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($data);

        // если заменен файл
        if (null !== $file) {
            // чистим старый файл и кэш
            $this->get('file')->cleanupFile($oldData);
        }

        $entityManager->flush();

        return $data;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function uploadAction(Request $request)
    {
        $form = $this->createForm(UploadType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->get('bot_checker')->checkRequest($request);

                    $file = $this->saveFileData($request, $form->getData());

                    // просмотр файла авторизованными пользователями
                    if ($this->isGranted('ROLE_USER')) {
                        $url = $this->generateUrl(
                            'file_view',
                            array(
                                'id' => $file->getId()
                            ),
                            Router::ABSOLUTE_URL
                        );
                    } else {
                        // неавторизованных перенаправляем на главную обменника
                        $url = $this->generateUrl(
                            'file_index',
                            array(),
                            Router::ABSOLUTE_URL
                        );
                    }

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:upload.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @param Request $request
     * @param File    $data
     * @throws FileDuplicatedException
     * @return File
     */
    protected function saveFileData(Request $request, File $data)
    {
        /** @var UploadedFile $file */
        $file = $data->getFile();

        $hash = \md5_file($file->getPathname());

        $existingFile = $this->getDoctrine()->getRepository('WapinetBundle:File')->findOneBy(array('hash' => $hash));
        if (null !== $existingFile) {
            throw new FileDuplicatedException($existingFile, $this->container);
        }

        $data->setHash($hash);
        $data->setMimeType($this->get('mime')->getMimeType($file->getClientOriginalName()));
        $data->setFileSize($file->getSize());
        $data->setOriginalFileName($file->getClientOriginalName());


        $data->setUser($this->getUser());
        $data->setIp($request->getClientIp());
        $data->setBrowser($request->headers->get('User-Agent', ''));

        if (null !== $data->getPlainPassword()) {
            $data->setFileTags(new ArrayCollection()); // не задаем тэги для запароленых файлов
            $data->setSaltValue();

            $encoder = $this->get('security.encoder_factory')->getEncoder($data);
            $password = $encoder->encodePassword($data->getPlainPassword(), $data->getSalt());
            $data->setPassword($password);

            // Запароленные файлы не скрываем
            $data->setHidden(false);
        }

        $this->makeFileTags($data);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($data);

        $entityManager->flush();

        $this->get('event_dispatcher')->dispatch(
            FileEvent::FILE_ADD,
            new FileEvent($data->getUser(), $data)
        );

        return $data;
    }


    /**
     * TODO: отрефакторить.
     * @param File $file
     */
    private function makeEditFileTags(File $file)
    {
        $manager = $this->getDoctrine()->getManager();

        // удаляем из коллекции устаревшие тэги
        $removedFileTagsCollection = $file->getFileTags()->filter(function (FileTags $oldFileTags) use ($file) {
            foreach ($file->getTags() as $newTag) {
                if ($newTag == $oldFileTags->getTag()) {
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
        $newTagsCollection = $file->getTags()->filter(function (Tag $newTag) use ($file) {
            foreach ($file->getFileTags() as $fileTags) {
                if ($newTag == $fileTags->getTag()) {
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


    /**
     * @param File $file
     */
    private function makeFileTags(File $file)
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
     * @param Request $request
     * @return JsonResponse
     */
    public function tagsSearchAction(Request $request)
    {
        $term = \trim($request->get('term', ''));
        if ('' === $term) {
            return new JsonResponse(array());
        }

        $exTerm = \explode(',', $term);
        $term = \ltrim(\end($exTerm));
        if ('' === $term) {
            return new JsonResponse(array());
        }

        $tags = $this->getDoctrine()->getRepository('WapinetBundle:Tag')->findLikeName($term);

        $result = array();
        foreach ($tags as $tag) {
            $result[] = $tag->getName();
        }

        return new JsonResponse($result);
    }


    /**
     * @param File $file
     * @return Response
     */
    public function swiperAction(File $file)
    {
        $repository = $this->getDoctrine()->getRepository('WapinetBundle:File');

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

        $response = $this->render('WapinetBundle:File:swiper.html.twig', array(
            'file' => $file,
            'prevFile' => $prevFile,
            'nextFile' => $nextFile,
        ));

        $this->incrementViews($file);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($file);
        $entityManager->flush();

        return $response;
    }
}