<?php

namespace App\Controller;

use App\Exception\ArchiverException;
use App\Form\Type\Archiver\AddType;
use App\Service\Archiver\Archive7z;
use App\Service\Archiver\ArchiveRar;
use App\Service\Archiver\ArchiveZip;
use App\Service\Translit;
use const DIRECTORY_SEPARATOR;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/archiver")
 */
class ArchiverController extends AbstractController
{
    /**
     * @Route("", name="archiver_index")
     */
    public function indexAction(): Response
    {
        return $this->render('Archiver/index.html.twig');
    }

    /**
     * @Route("/create", name="archiver_create")
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

                    $archive = \basename($archiveDirectory);

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

    /**
     * @Route("/edit/{archive}", name="archiver_edit", requirements={"archive": "archive[a-z0-9]+"})
     */
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

    /**
     * @Route("/download/{archive}.zip", name="archiver_download", requirements={"archive": "archive[a-z0-9]+"})
     */
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
     * @Route("/download/{archive}/{name}", name="archiver_download_file", requirements={"archive": "archive[a-z0-9]+"})
     */
    public function downloadFileAction(Request $request, string $archive, string $name): BinaryFileResponse
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = new BinaryFileResponse(
            $this->get(\App\Service\File\File::class)->checkFile($archiveDirectory, $path, false)
        );

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $name,
            $this->get(Translit::class)->toAscii($name)
        );

        return $file;
    }

    /**
     * @Route("/delete/{archive}/{name}", name="archiver_delete_file", methods={"POST"}, requirements={"archive": "archive[a-z0-9]+"}, options={"expose": true})
     */
    public function deleteFileAction(Request $request, string $archive, $name): RedirectResponse
    {
        $path = $request->get('path');
        $archiveDirectory = $this->checkArchiveDirectory($archive);

        $file = $this->get(\App\Service\File\File::class)->checkFile($archiveDirectory, $path, true);

        $this->get(Filesystem::class)->remove($file);

        return $this->redirectToRoute('archiver_edit', ['archive' => $archive]);
    }

    /**
     * @Route("/extract", name="archiver_extract")
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

                    $archive = \basename($archiveDirectory);

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
     * @throws ArchiverException
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

    protected function generateArchiveName(): string
    {
        return \uniqid('archive', false);
    }

    /**
     * @throws FileException
     */
    protected function checkArchiveDirectory(string $archive): string
    {
        $directory = $this->getParameter('kernel.tmp_archiver_dir').DIRECTORY_SEPARATOR.$archive;

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
     * @throws IOException
     */
    protected function createArchiveDirectory(): string
    {
        $directory = $this->getParameter('kernel.tmp_archiver_dir').DIRECTORY_SEPARATOR.$this->generateArchiveName();
        $this->get(Filesystem::class)->mkdir($directory);

        return $directory;
    }

    /**
     * @throws FileException
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
        $services[\App\Service\File\File::class] = '?'.\App\Service\File\File::class;

        return $services;
    }
}
