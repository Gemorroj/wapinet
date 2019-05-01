<?php

namespace App\Controller;

use App\Exception\ArchiverException;
use App\Form\Type\Archiver\AddType;
use App\Helper\Archiver\Archive7z;
use App\Helper\Archiver\ArchiveRar;
use App\Helper\Archiver\ArchiveZip;
use App\Helper\Translit;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use function basename;
use function is_dir;
use function is_readable;
use function is_writable;
use function mb_strpos;
use function realpath;
use function str_replace;
use function uniqid;
use const DIRECTORY_SEPARATOR;

class ArchiverController extends AbstractController
{
    public function indexAction(): Response
    {
        return $this->render('Archiver/index.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request): Response
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

                    return $this->redirectToRoute('archiver_edit', ['archive' => $archive]);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Archiver/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function editAction(Request $request, string $archive): Response
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
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        $files = $this->get(Archive7z::class)->getFiles($archiveDirectory);

        return $this->render('Archiver/edit.html.twig', [
            'archive' => $archive,
            'form' => $form->createView(),
            'files' => $files,
        ]);
    }

    public function downloadAction(string $archive): BinaryFileResponse
    {
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $name = $archive.'.zip';
        $archiveZip = $this->get(ArchiveZip::class);

        $file = new BinaryFileResponse($archiveZip->create($archiveDirectory));

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->get(Translit::class)->toAscii($name)
        );

        return $file;
    }

    /**
     * @param Request $request
     * @param string  $archive
     * @param string  $name
     *
     * @throws AccessDeniedException
     *
     * @return BinaryFileResponse
     */
    public function downloadFileAction(Request $request, string $archive, string $name): BinaryFileResponse
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = new BinaryFileResponse(
            $this->get(\App\Helper\File\File::class)->checkFile($archiveDirectory, $path, false)
        );

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->get(Translit::class)->toAscii($name)
        );

        return $file;
    }

    /**
     * @param Request $request
     * @param string  $archive
     * @param string  $name
     *
     * @return RedirectResponse
     */
    public function deleteFileAction(Request $request, string $archive, $name): RedirectResponse
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = $this->get(\App\Helper\File\File::class)->checkFile($archiveDirectory, $path, true);

        $this->get(Filesystem::class)->remove($file);

        return $this->redirectToRoute('archiver_edit', ['archive' => $archive]);
    }

    /**
     * @param string $archiveDirectory
     * @param string $path
     * @param bool   $allowDirectory
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    protected function checkFile(string $archiveDirectory, string $path, bool $allowDirectory = false): string
    {
        $path = str_replace('\\', '/', $path);

        if (false !== mb_strpos($path, '../')) {
            throw $this->createAccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        $file = realpath($archiveDirectory. DIRECTORY_SEPARATOR.$path);

        if (false === $file) {
            throw $this->createNotFoundException('Файл не найден: "'.$path.'"".');
        }

        if (true !== $allowDirectory && true === is_dir($allowDirectory)) {
            throw $this->createAccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        return $file;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function extractAction(Request $request): Response
    {
        $form = $this->createForm(AddType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $archiveDirectory = $this->extractArchive($data['file']);

                    $archive = basename($archiveDirectory);

                    return $this->redirectToRoute('archiver_edit', ['archive' => $archive]);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Archiver/exctract.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param File $file
     *
     * @throws ArchiverException
     *
     * @return string
     */
    protected function extractArchive(File $file): string
    {
        $archiveZip = $this->get(ArchiveZip::class);
        if (true === $archiveZip->isValid($file)) {
            $archiveDirectory = $this->createArchiveDirectory();
            $archiveZip->extract($archiveDirectory, $file);

            return $archiveDirectory;
        }
        //$archiveRar = $this->get(ArchiveRar::class);
        //if (true === $archiveRar->isValid($file)) {
        //    $archiveDirectory = $this->createArchiveDirectory();
        //    $archiveRar->extract($archiveDirectory, $file);
        //    return $archiveDirectory;
        //}
        $archive7z = $this->get(Archive7z::class);
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
    protected function generateArchiveName(): string
    {
        return uniqid('archive', false);
    }

    /**
     * @param string $archive
     *
     * @throws FileException
     *
     * @return string
     */
    protected function checkArchiveDirectory(string $archive): string
    {
        $directory = $this->getParameter('kernel.tmp_archiver_dir'). DIRECTORY_SEPARATOR.$archive;

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
     * @throws IOException
     *
     * @return string
     */
    protected function createArchiveDirectory(): string
    {
        $directory = $this->getParameter('kernel.tmp_archiver_dir'). DIRECTORY_SEPARATOR.$this->generateArchiveName();
        $this->get(Filesystem::class)->mkdir($directory);

        return $directory;
    }

    /**
     * @param string       $directory
     * @param UploadedFile $file
     *
     * @throws FileException
     *
     * @return File
     */
    protected function addFile(string $directory, UploadedFile $file): File
    {
        return $file->move($directory, $file->getClientOriginalName());
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Translit::class] = '?'.Translit::class;
        $services[Archive7z::class] = '?'.Archive7z::class;
        $services[ArchiveZip::class] = '?'.ArchiveZip::class;
        $services[ArchiveRar::class] = '?'.ArchiveRar::class;
        $services[Filesystem::class] = '?'.Filesystem::class;
        $services[\App\Helper\File\File::class] = '?'.\App\Helper\File\File::class;

        return $services;
    }
}
