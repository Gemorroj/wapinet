<?php

namespace App\Controller;

use App\Exception\ArchiverException;
use App\Form\Type\Archiver\AddType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ArchiverController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('Archiver/index.html.twig');
    }


    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $form = $this->createForm(AddType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $archiveDirectory = $this->createArchiveDirectory();
                    $this->addFile($archiveDirectory, $data['file']);

                    $archive = basename($archiveDirectory);

                    return $this->redirectToRoute('archiver_edit', array('archive' => $archive));
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Archiver/create.html.twig', array(
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
        $form = $this->createForm(AddType::class);
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

        $files = $this->get('archive_7z')->getFiles($archiveDirectory);

        return $this->render('Archiver/edit.html.twig', array(
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

        $name = $archive . '.zip';
        $archiveZip = $this->get('archive_zip');

        $file = new BinaryFileResponse($archiveZip->create($archiveDirectory));

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->get('translit')->toAscii($name)
        );

        return $file;
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

        $file = new BinaryFileResponse(
            $this->get('file')->checkFile($archiveDirectory, $path, false)
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
     * @param string $archive
     * @param string $name
     * @return RedirectResponse
     */
    public function deleteFileAction(Request $request, $archive, $name)
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = $this->get('file')->checkFile($archiveDirectory, $path, true);

        $this->get('filesystem')->remove($file);

        return $this->redirectToRoute('archiver_edit', array('archive' => $archive));
    }


    /**
     * @param string $archiveDirectory
     * @param string $path
     * @param bool $allowDirectory
     * @throws AccessDeniedException
     * @return string
     */
    protected function checkFile($archiveDirectory, $path, $allowDirectory = false)
    {
        $path = \str_replace('\\', '/', $path);

        if (false !== \strpos($path, '../')) {
            throw $this->createAccessDeniedException('Запрещен доступ: "' . $path . '"".');
        }

        $file = \realpath($archiveDirectory . \DIRECTORY_SEPARATOR . $path);

        if (false === $file) {
            throw $this->createNotFoundException('Файл не найден: "' . $path . '"".');
        }

        if (true !== $allowDirectory && true === \is_dir($allowDirectory)) {
            throw $this->createAccessDeniedException('Запрещен доступ: "' . $path . '"".');
        }

        return $file;
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function extractAction(Request $request)
    {
        $form = $this->createForm(AddType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $archiveDirectory = $this->extractArchive($data['file']);

                    $archive = \basename($archiveDirectory);

                    return $this->redirectToRoute('archiver_edit', array('archive' => $archive));
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Archiver/exctract.html.twig', array(
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
        //$archiveRar = $this->get('archive_rar');
        //if (true === $archiveRar->isValid($file)) {
        //    $archiveDirectory = $this->createArchiveDirectory();
        //    $archiveRar->extract($archiveDirectory, $file);
        //    return $archiveDirectory;
        //}
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
        return uniqid('archive', false);
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
        $directory = $this->getTmpDir() . \DIRECTORY_SEPARATOR . $archive;

        if (false === \is_dir($directory)) {
            throw new FileException('Не удалось найти временную директорию');
        }
        if (false === \is_readable($directory)) {
            throw new FileException('Нет доступа на чтение временной директории');
        }
        if (false === \is_writable($directory)) {
            throw new FileException('Нет доступа на запись во временную директорию');
        }

        return $directory;
    }


    /**
     * @return string
     * @throws IOException
     */
    protected function createArchiveDirectory()
    {
        $directory = $this->getTmpDir() . \DIRECTORY_SEPARATOR . $this->generateArchiveName();
        $this->get('filesystem')->mkdir($directory);

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