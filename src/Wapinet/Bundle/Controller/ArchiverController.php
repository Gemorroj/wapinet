<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Wapinet\Bundle\Exception\ArchiverException;
use Wapinet\Bundle\Form\Type\Archiver\AddType;

class ArchiverController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Archiver:index.html.twig');
    }


    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $form = $this->createForm(new AddType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $archiveDirectory = $this->createArchiveDirectory();
                    $this->addFile($archiveDirectory, $data['file']);

                    $archive = basename($archiveDirectory);
                    return new RedirectResponse($this->get('router')->generate('archiver_edit', array('archive' => $archive), Router::ABSOLUTE_URL));
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Archiver:create.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @param Request $request
     * @param string $archive
     * @return Response
     */
    public function editAction(Request $request, $archive)
    {
        $form = $this->createForm(new AddType());
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        try {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $this->addFile($archiveDirectory, $data['file']);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        $files = $this->get('archive_zip')->getFiles($archiveDirectory);

        return $this->render('WapinetBundle:Archiver:edit.html.twig', array(
            'archive' => $archive,
            'form' => $form->createView(),
            'files' => $files,
        ));
    }


    /**
     * @param string $archive
     * @return BinaryFileResponse
     */
    public function downloadAction($archive)
    {
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $archiveZip = $this->get('archive_zip');
        $file = $archiveZip->create($archiveDirectory);

        return new BinaryFileResponse($file);
    }

    /**
     * @param Request $request
     * @param string $archive
     * @param string $name
     * @return BinaryFileResponse
     * @throws AccessDeniedException
     */
    public function downloadFileAction(Request $request, $archive, $name)
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = $this->checkFile($archiveDirectory, $path, false);

        return new BinaryFileResponse($file);
    }

    /**
     * @param Request $request
     * @param string $archive
     * @param string $name
     * @return RedirectResponse
     */
    public function deleteFileAction(Request $request, $archive, $name)
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = $this->checkFile($archiveDirectory, $path, true);

        $fs = new Filesystem();
        $fs->remove($file);

        return new RedirectResponse($this->get('router')->generate('archiver_edit', array('archive' => $archive), Router::ABSOLUTE_URL));
    }


    /**
     * @param string $archiveDirectory
     * @param string $path
     * @param bool $allowDirectory
     * @throws AccessDeniedException
     * @return File
     */
    protected function checkFile($archiveDirectory, $path, $allowDirectory = false)
    {
        if (strpos($path, '../') !== false || strpos($path, '..\\')) {
            throw new AccessDeniedException($path);
        }

        $file = realpath($archiveDirectory . DIRECTORY_SEPARATOR . $path);

        if (strpos($file, $archiveDirectory) !== 0) {
            throw new AccessDeniedException($path);
        }

        if (true !== $allowDirectory && true === is_dir($allowDirectory)) {
            throw new AccessDeniedException($path);
        }

        return new File($file);
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function extractAction(Request $request)
    {
        $form = $this->createForm(new AddType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $archiveDirectory = $this->extractArchive($data['file']);

                    $archive = basename($archiveDirectory);
                    return new RedirectResponse($this->get('router')->generate('archiver_edit', array('archive' => $archive), Router::ABSOLUTE_URL));
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Archiver:exctract.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    /**
     * @param File $file
     * @throws ArchiverException
     * @return string
     */
    protected function extractArchive(File $file)
    {
        $archiveZip = $this->get('archive_zip');
        if (true === $archiveZip->isValid($file)) {
            $archiveDirectory = $this->createArchiveDirectory();
            $archiveZip->extract($archiveDirectory, $file);
            return $archiveDirectory;
        }
        $archiveRar = $this->get('archive_rar');
        if (true === $archiveRar->isValid($file)) {
            $archiveDirectory = $this->createArchiveDirectory();
            $archiveRar->extract($archiveDirectory, $file);
            return $archiveDirectory;
        }
        $archive7z = $this->get('archive_7z');
        if (true === $archive7z->isValid($file)) {
            $archiveDirectory = $this->createArchiveDirectory();
            $archive7z->extract($archiveDirectory, $file);
            return $archiveDirectory;
        }

        throw new ArchiverException('Неподдерживаемый тип архива');
    }


    /**
     * @return string
     */
    protected function generateArchiveName()
    {
        return uniqid('archive');
    }

    /**
     * @return string
     */
    protected function getTmpDir()
    {
        return $this->get('kernel')->getTmpArchiverDir();
    }

    /**
     * @param string $archive
     * @return string
     * @throws FileException
     */
    protected function checkArchiveDirectory($archive)
    {
        $directory = $this->getTmpDir() . DIRECTORY_SEPARATOR . $archive;

        if (false === is_dir($directory)) {
            throw new FileException('Не удалось найти временную директорию');
        }
        if (false === is_readable($directory)) {
            throw new FileException('Нет доступа на чтение временной директории');
        }
        if (false === is_writable($directory)) {
            throw new FileException('Нет доступа на запись во временную директорию');
        }

        return $directory;
    }


    /**
     * @return string
     * @throws \RuntimeException
     */
    protected function createArchiveDirectory()
    {
        $archive = $this->generateArchiveName();
        $directory = $this->getTmpDir() . DIRECTORY_SEPARATOR . $archive;
        if (false === mkdir($directory, 0755)) {
            throw new \RuntimeException('Не удалось создать временную директорию');
        }

        return $directory;
    }

    /**
     * @param string $directory
     * @param UploadedFile $file
     * @throws FileException
     * @return File
     */
    protected function addFile($directory, UploadedFile $file)
    {
        return $file->move($directory, $file->getClientOriginalName());
    }
}
