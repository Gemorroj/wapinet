<?php

namespace App\Controller;

use App\Entity\File\FileContent;
use App\Exception\AudioException;
use App\Form\Type\AudioTags\AudioTagsEditType;
use App\Form\Type\AudioTags\AudioTagsType;
use App\Service\Getid3;
use App\Service\Translit;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see https://github.com/JamesHeinrich/getID3/blob/master/demos/demo.audioinfo.class.php
 * @see https://github.com/JamesHeinrich/getID3/blob/master/demos/demo.write.php
 */
#[Route('/audio_tags')]
class AudioTagsController extends AbstractController
{
    #[Route(path: '', name: 'audio_tags_index')]
    public function indexAction(Request $request): Response
    {
        $form = $this->createForm(AudioTagsType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $file = $this->saveFile($data);

                    return $this->redirectToRoute('audio_tags_edit', [
                        'fileName' => $file->getFilename(),
                        'originalFileName' => $data['file']->getClientOriginalName(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('AudioTags/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function saveFile(array $data): File
    {
        /** @var UploadedFile $file */
        $file = $data['file'];
        $tempDirectory = $this->getParameter('kernel.tmp_dir');
        $tempName = \tempnam($tempDirectory, 'audio_file');
        if (false === $tempName) {
            throw new \RuntimeException('Не удалось создать временный файл');
        }

        return $file->move($tempDirectory, $tempName);
    }

    #[Route(path: '/edit/{fileName}/{originalFileName}', name: 'audio_tags_edit')]
    public function editAction(Request $request, string $fileName, string $originalFileName): Response
    {
        $form = $this->createForm(AudioTagsEditType::class);

        $info = $this->getInfo($fileName);
        $comments = $info['comments'] ?? [];

        $this->setFormData($form, $comments);
        $originalForm = clone $form; // hack stupid symfony

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->setTags($request, $fileName, $form, $info);

                    $form = $originalForm;
                    $info = $this->getInfo($fileName);
                    $comments = $info['comments'] ?? [];
                    $this->setFormData($form, $comments);
                }
            }
        } catch (AudioException $e) {
            foreach ($e->getMessages() as $message) {
                $form->addError(new FormError($message));
            }
        } catch (\Exception $e) {
            $this->container->get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e, 'trace' => $e->getTraceAsString()]);
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('AudioTags/edit.html.twig', [
            'form' => $form->createView(),
            'info' => $info,
            'originalFileName' => $originalFileName,
            'fileName' => $fileName,
        ]);
    }

    private function setFormData(FormInterface $form, array $tags): void
    {
        $data = [
            // 'picture' => null,
            'title' => $tags['title'][0] ?? '',
            'album_artist' => $tags['album_artist'][0] ?? ($tags['albumartist'][0] ?? ($tags['band'][0] ?? '')),
            'artist' => $tags['artist'][0] ?? '',
            'album' => $tags['album'][0] ?? '',
            'year' => $tags['year'][0] ?? ($tags['date'][0] ?? ''),
            'track_number' => $tags['track_number'][0] ?? ($tags['track'][0] ?? ''),
            'url_user' => $tags['url_user'][0] ?? '',
            'genre' => $tags['genre'][0] ?? '',
            'comment' => $tags['comment'][0] ?? '',
        ];

        if (isset($tags['picture'][0])) {
            $data['picture'] = new FileContent(
                $tags['picture'][0]['data'],
                $tags['picture'][0]['image_mime'],
                'image'
            );
        }

        $form->setData($data);
    }

    private function getInfo(string $fileName): array
    {
        $getid3 = $this->container->get(Getid3::class)->getId3();
        $info = $getid3->analyze($this->getFilePath($fileName));
        \getid3_lib::CopyTagsToComments($info);

        if (!isset($info['tags'])) {
            $info['tags'] = [];
        }

        return $info;
    }

    /**
     * @throws AudioException
     */
    private function setTags(Request $request, string $fileName, FormInterface $form, array $info): void
    {
        $data = $form->getData();
        $writer = $this->container->get(Getid3::class)->getId3Writer();
        if ($data['remove_other_tags']) {
            $writer->remove_other_tags = true;
        }
        $writer->overwrite_tags = true;
        $writer->tagformats = $this->getAllowedTagFormats($info);
        $writer->filename = $this->getFilePath($fileName);

        $writer->tag_data = [
            'title' => [$data['title']],

            'album_artist' => [$data['album_artist']],
            'albumartist' => [$data['album_artist']],
            'band' => [$data['album_artist']],

            'artist' => [$data['artist']],
            'album' => [$data['album']],

            'year' => [$data['year']],
            'date' => [$data['year']],

            'track_number' => [$data['track_number']],
            'track' => [$data['track_number']],

            'url_user' => [$data['url_user']],
            'genre' => [$data['genre']],
            'comment' => [$data['comment']],
            // 'picture' => null,
        ];

        $requestForm = $request->request->get($form->getName());

        if (isset($info['comments']['picture'][0]) && (!isset($requestForm['picture']['file_url_delete']) || !$requestForm['picture']['file_url_delete'])) {
            $writer->tag_data['attached_picture'][0]['data'] = $info['comments']['picture'][0]['data'];
            $writer->tag_data['attached_picture'][0]['picturetypeid'] = 0;
            $writer->tag_data['attached_picture'][0]['description'] = 'image';
            $writer->tag_data['attached_picture'][0]['mime'] = $info['comments']['picture'][0]['image_mime'];
        }

        $picture = $data['picture'];
        if ($picture instanceof UploadedFile) {
            $data = \file_get_contents($picture->getPathname());
            if (false === $data) {
                throw new IOException('Не удалось получить изображение.', 0, null, $picture->getPathname());
            }

            $writer->tag_data['attached_picture'][0]['data'] = $data;
            $writer->tag_data['attached_picture'][0]['picturetypeid'] = 0;
            $writer->tag_data['attached_picture'][0]['description'] = 'image';
            $writer->tag_data['attached_picture'][0]['mime'] = $picture->getMimeType();

            unset($data); // чистим память
        }

        if (!$writer->WriteTags()) {
            throw new AudioException($writer->errors);
        }
    }

    /**
     * @param array $info (поддерживаются mp3, mp2, mp1, riff, mpc, flac, real, ogg)
     */
    private function getAllowedTagFormats(array $info): array
    {
        switch ($info['fileformat']) {
            case 'mp3':
            case 'mp2':
            case 'mp1':
            case 'riff': // maybe not officially, but people do it anyway
                return ['id3v1', /* 'id3v2.2', */ 'id3v2.3', 'id3v2.4'/* 'ape', 'lyrics3' */];
                break;

            case 'mpc':
                return ['ape'];
                break;

            case 'flac':
                return ['metaflac'];
                break;

            case 'real':
                return ['real'];
                break;

            case 'ogg':
                switch ($info['audio']['dataformat']) {
                    case 'flac':
                        // metaflac is not (yet) compatible with OggFLAC files
                        // $AllowedTagFormats = ['metaflac'];
                        break;
                    case 'vorbis':
                        return ['vorbiscomment'];
                        break;
                    default:
                        // metaflac is not (yet) compatible with Ogg files other than OggVorbis
                        break;
                }
                break;
        }

        return [];
    }

    #[Route(path: '/download/{fileName}/{originalFileName}', name: 'audio_tags_download')]
    public function downloadAction(string $fileName, string $originalFileName): BinaryFileResponse
    {
        $file = new BinaryFileResponse($this->getFilePath($fileName));

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $originalFileName,
            $this->container->get(Translit::class)->toAscii($originalFileName)
        );

        return $file;
    }

    private function getFilePath(string $fileName): string
    {
        return $this->getParameter('kernel.tmp_dir').\DIRECTORY_SEPARATOR.$fileName;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Translit::class] = '?'.Translit::class;
        $services[Getid3::class] = '?'.Getid3::class;
        $services[LoggerInterface::class] = '?'.LoggerInterface::class;

        return $services;
    }
}
