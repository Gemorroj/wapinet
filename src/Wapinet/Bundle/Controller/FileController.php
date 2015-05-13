<?php

namespace Wapinet\Bundle\Controller;

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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Event\FileEvent;
use Wapinet\Bundle\Exception\FileDuplicatedException;
use Wapinet\Bundle\Form\Type\File\EditType;
use Wapinet\Bundle\Form\Type\File\PasswordType;
use Wapinet\Bundle\Form\Type\File\SearchType;
use Wapinet\Bundle\Form\Type\File\UploadType;
use Wapinet\UserBundle\Entity\User;

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
        $comments = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->getComments();
        return $this->render('WapinetBundle:File:index.html.twig', array('comments' => $comments));
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
        $statistic = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->getStatistic();

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
        $form = $this->createForm(new SearchType());
        $pagerfanta = null;
        $session = $this->get('session');

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $key = \uniqid();
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
        $client = $this->container->get('sphinx');
        $client->setPage($page);

        if ('date' === $data['sort']) {
            $client->SetSortMode(SPH_SORT_ATTR_DESC, 'created_at_ts');
        } else {
            $client->SetSortMode(SPH_SORT_RELEVANCE);
        }

        $client->AddQuery($data['search'], 'files');

        $result = $client->RunQueries();
        if (false === $result) {
            throw new \RuntimeException($client->GetLastError());
        }

        return $client->getPagerfanta($result, 'Wapinet\Bundle\Entity\File');
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
        if (!$this->getUser()->hasRole('ROLE_ADMIN') && !$this->getUser()->hasRole('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException('Доступ запрещен.');
        }

        $page = $request->get('page', 1);

        $query = $this->getDoctrine()
            ->getRepository('Wapinet\Bundle\Entity\File')
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
        $tagManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Tag');
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
        $tagManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Tag');

        $tag = $tagManager->getTagByName($tagName);
        if (null === $tag) {
            throw $this->createNotFoundException('Тэг не найден');
        }

        $fileManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File');
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

        $tagManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File');

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
            ->getRepository('Wapinet\Bundle\Entity\File')
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
        if (null !== $file->getPassword() && (!($this->getUser() instanceof User) || !($file->getUser() instanceof User) || $file->getUser()->getId() !== $this->getUser()->getId())) {
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
        $response = $this->render('WapinetBundle:File:view.html.twig', array('comments_id' => 'file-' . $file->getId(), 'file' => $file));
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
        $form = $this->createForm(new PasswordType());
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
        $path = $request->get('path');
        $tmpDir = $this->get('kernel')->getTmpFileDir();

        $entry = $tmpDir . \DIRECTORY_SEPARATOR . \str_replace('\\', '/', $path);

        if (!\file_exists($entry)) {
            $file = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->find($id);
            if (null !== $file) {
                $this->createNotFoundException('Файл не найден.');
            }

            $archive = $this->get('archive_7z');

            $archive->extractEntry($file->getFile(), $path, $tmpDir);
        }

        return new BinaryFileResponse($entry);
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
     * @return RedirectResponse|JsonResponse|Response
     */
    public function editAction(Request $request, File $file)
    {
        $this->denyAccessUnlessGranted('EDIT', $file);


        $fileHelper = $this->get('file');
        $tagsString = $fileHelper->joinTagNames($file->getTags());

        $form = $this->createForm(new EditType($tagsString));
        $form->setData($file);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $newFile = $form->getData();
                    $tagsString = $form['tags_string']->getData();
                    $this->editFileData($request, $file, $newFile, $tagsString);

                    $url = $this->generateUrl('file_view', array('id' => $file->getId()), Router::ABSOLUTE_URL);

                    // загрузка через ajax
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(array('url' => $url));
                    }

                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        // загрузка через ajax
        if ($request->isXmlHttpRequest() /*&& $form->isSubmitted()*/ && !$form->isValid()) {
            return new JsonResponse(array(
                'errors' => $this->get('error')->makeErrors($form),
            ), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('WapinetBundle:File:edit.html.twig', array(
            'form' => $form->createView(),
            'file' => $file,
        ));
    }


    /**
     * @param Request $request
     * @param File    $data
     * @param File    $newData
     * @param string $tagsString
     * @throws FileDuplicatedException
     * @return File
     */
    protected function editFileData(Request $request, File $data, File $newData, $tagsString = null)
    {
        $oldData = clone $data;

        /** @var UploadedFile|null $file */
        $file = $newData->getFile();
        if (null !== $file) {
            $hash = \md5_file($file->getPathname());

            $existingFile = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->findOneBy(array('hash' => $hash));
            if (null !== $existingFile) {
                throw new FileDuplicatedException($existingFile, $this->container);
            }

            $data->setHash($hash);
            $data->setMimeType($this->get('mime')->getMimeType($file->getClientOriginalName()));
            $data->setFileSize($file->getSize());
            $data->setOriginalFileName($file->getClientOriginalName());
        }

        // обновляем ip и браузер только если файл редактирует владелец
        if ($data->getUser()->getId() === $this->getUser()->getId()) {
            //$data->setUser($this->getUser());
            $data->setIp($request->getClientIp());
            $data->setBrowser($request->headers->get('User-Agent', ''));
        }

        $data->setUpdatedAtValue();
        $data->setTags(new ArrayCollection());

        if (null !== $newData->getPassword()) {
            $this->get('file')->setPassword($data, $newData->getPassword());
        } else {
            $this->get('file')->removePassword($data);

            // тэги только у незапароленых файлов
            $this->saveTags($data, $tagsString);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->merge($data);

        if (null !== $file) {
            // файл и кэш
            $this->get('file')->cleanupFile($oldData);
        } else {
            // если файлу задали пароль, чистим старые тэги
            if (null !== $newData->getPassword()) {
                $this->get('file')->cleanupFileTags($oldData);
            }
        }

        $entityManager->flush();

        return $data;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|JsonResponse|Response
     */
    public function uploadAction(Request $request)
    {
        $form = $this->createForm(new UploadType($this->container));

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->get('bot_checker')->checkRequest($request);

                    $data = $form->getData();
                    $tagsString = $form['tags_string']->getData();
                    $file = $this->saveFileData($request, $data, $tagsString);

                    $url = $this->generateUrl('file_view', array(
                            'id' => $file->getId()
                        ), Router::ABSOLUTE_URL
                    );

                    // загрузка через ajax
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(array(
                            'id' => $file->getId(),
                            'url' => $url
                        ));
                    }

                    // обычная загрузка
                    return $this->redirect($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        // загрузка через ajax
        if ($request->isXmlHttpRequest() /*&& $form->isSubmitted()*/ && !$form->isValid()) {
            return new JsonResponse(array(
                'errors' => $this->get('error')->makeErrors($form),
            ), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('WapinetBundle:File:upload.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @param File $file
     * @param string $tagsString
     */
    protected function saveTags(File $file, $tagsString = null)
    {
        if (null !== $tagsString) {
            $tagManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Tag');
            $fileHelper = $this->get('file');
            $tagsArray = $fileHelper->splitTagNames($tagsString);
            $tagsObjectArray = $tagManager->loadOrCreateTags($tagsArray, $file);
            $file->setTags($tagsObjectArray);
        }
    }


    /**
     * @param File $file
     * @see http://symfony.com/doc/current/cookbook/security/acl.html
     */
    protected function saveFileAcl(File $file)
    {
        $user = $this->getUser();
        if (null !== $user) {
            // creating the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($file);

            try {
                $acl = $aclProvider->findAcl($objectIdentity);
            } catch (\Symfony\Component\Security\Acl\Exception\AclNotFoundException $e) {
                $acl = $aclProvider->createAcl($objectIdentity);
            }

            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            // grant owner access
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);
        }
    }


    /**
     * @param Request $request
     * @param File    $data
     * @param string $tagsString
     * @throws FileDuplicatedException
     * @return File
     */
    protected function saveFileData(Request $request, File $data, $tagsString = null)
    {
        /** @var UploadedFile $file */
        $file = $data->getFile();

        $hash = \md5_file($file->getPathname());

        $existingFile = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File')->findOneBy(array('hash' => $hash));
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

        if (null !== $data->getPassword()) {
            $data->setSaltValue();

            $encoder = $this->get('security.encoder_factory')->getEncoder($data);
            $password = $encoder->encodePassword($data->getPassword(), $data->getSalt());
            $data->setPassword($password);

            // Запароленные файлы не скрываем
            $data->setHidden(false);
        } else {
            // тэги только у незапароленых файлов
            $this->saveTags($data, $tagsString);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($data);

        $entityManager->flush();
        $this->saveFileAcl($data);

        $this->container->get('event_dispatcher')->dispatch(
            FileEvent::FILE_ADD,
            new FileEvent($data->getUser(), $data)
        );

        return $data;
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
        $repository = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\File');

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
