<?php

namespace App\Controller;

use App\Entity\File\FileContent;
use App\Exception\AudioException;
use App\Form\Type\AudioTags\AudioTagsEditType;
use App\Form\Type\AudioTags\AudioTagsType;
use App\Helper\Getid3;
use App\Helper\Translit;
use Exception;
use getid3_lib;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use function file_get_contents;
use function tempnam;
use const DIRECTORY_SEPARATOR;

/**
 * @see https://github.com/JamesHeinrich/getID3/blob/master/demos/demo.audioinfo.class.php
 * @see https://github.com/JamesHeinrich/getID3/blob/master/demos/demo.write.php
 */
class AudioTagsController extends AbstractController
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
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
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('AudioTags/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function saveFile(array $data): File
    {
        /** @var UploadedFile $file */
        $file = $data['file'];
        $tempDirectory = $this->getParameter('kernel.tmp_dir');
        $tempName = tempnam($tempDirectory, 'audio_file');
        if (false === $tempName) {
            throw new RuntimeException('Не удалось создать временный файл');
        }

        return $file->move($tempDirectory, $tempName);
    }

    public function editAction(Request $request, string $fileName, string $originalFileName): Response
    {
        $form = $this->createForm(AudioTagsEditType::class);

        $info = $this->getInfo($fileName);
        $comments = $info['comments'] ?? [];

        $this->setFormData($form, $comments);
        $originalForm = clone $form; //hack stupid symfony

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $this->setTags($fileName, $form, $info);

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
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('AudioTags/edit.html.twig', [
            'form' => $form->createView(),
            'info' => $info,
            'originalFileName' => $originalFileName,
            'fileName' => $fileName,
        ]);
    }

    protected function setFormData(FormInterface $form, array $tags): void
    {
        $data = [
            //'picture' => null,
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

    protected function getInfo(string $fileName): array
    {
        $getid3 = $this->get(Getid3::class)->getId3();
        $info = $getid3->analyze($this->getFilePath($fileName));
        getid3_lib::CopyTagsToComments($info);

        if (!isset($info['tags'])) {
            $info['tags'] = [];
        }

        return $info;
    }

    /**
     * @param string        $fileName
     * @param FormInterface $form
     * @param array         $info
     *
     * @throws AudioException
     */
    protected function setTags(string $fileName, FormInterface $form, array $info): void
    {
        $data = $form->getData();
        $writer = $this->get(Getid3::class)->getId3Writer();
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
            //'picture' => null,
        ];

        $requestForm = $this->get('request_stack')->getCurrentRequest()->get($form->getName());

        if (isset($info['comments']['picture'][0]) && (!isset($requestForm['picture']['file_url_delete']) || !$requestForm['picture']['file_url_delete'])) {
            $writer->tag_data['attached_picture'][0]['data'] = $info['comments']['picture'][0]['data'];
            $writer->tag_data['attached_picture'][0]['picturetypeid'] = 0;
            $writer->tag_data['attached_picture'][0]['description'] = 'image';
            $writer->tag_data['attached_picture'][0]['mime'] = $info['comments']['picture'][0]['image_mime'];
        }

        $picture = $data['picture'];
        if ($picture instanceof UploadedFile) {
            $data = file_get_contents($picture->getPathname());
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
     *
     * @return array
     */
    protected function getAllowedTagFormats(array $info): array
    {
        switch ($info['fileformat']) {
            case 'mp3':
            case 'mp2':
            case 'mp1':
            case 'riff': // maybe not officially, but people do it anyway
                return ['id3v1', /*'id3v2.2', */'id3v2.3', 'id3v2.4'/*'ape', 'lyrics3'*/];
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

    /**
     * @param string $fileName
     * @param string $originalFileName
     *
     * @return BinaryFileResponse
     */
    public function downloadAction(string $fileName, string $originalFileName): BinaryFileResponse
    {
        $file = new BinaryFileResponse($this->getFilePath($fileName));

        $file->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $originalFileName,
            $this->get(Translit::class)->toAscii($originalFileName)
        );

        return $file;
    }

    protected function getFilePath(string $fileName): string
    {
        return $this->getParameter('kernel.tmp_dir'). DIRECTORY_SEPARATOR.$fileName;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Translit::class] = '?'.Translit::class;
        $services[Getid3::class] = '?'.Getid3::class;

        return $services;
    }
}
