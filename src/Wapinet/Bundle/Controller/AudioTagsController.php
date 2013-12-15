<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\Bundle\Exception\AudioException;
use Wapinet\Bundle\Form\Type\AudioTags\AudioTagsType;
use Wapinet\Bundle\Form\Type\AudioTags\AudioTagsEditType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Wapinet\UploaderBundle\Entity\FileContent;

/**
 * @see https://github.com/JamesHeinrich/getID3/blob/master/demos/demo.audioinfo.class.php
 * @see https://github.com/JamesHeinrich/getID3/blob/master/demos/demo.write.php
 */
class AudioTagsController extends Controller
{
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new AudioTagsType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $file = $this->saveFile($data);
                    $router = $this->container->get('router');

                    return $this->redirect(
                        $router->generate('audio_tags_edit', array(
                                'fileName' => $file->getBasename(),
                                'originalFileName' => $data['file']->getClientOriginalName()
                            ), Router::ABSOLUTE_URL
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:AudioTags:index.html.twig', array(
            'form' => $form->createView()
        ));
    }


    /**
     * @param array $data
     * @return File
     */
    protected function saveFile(array $data)
    {
        /** @var $file UploadedFile */
        $file = $data['file'];
        $tempDirectory = \AppKernel::getTmpDir();
        $tempName = tempnam($tempDirectory, 'audio_file');

        return $file->move($tempDirectory, $tempName);
    }


    /**
     * @param Request $request
     * @param string $fileName
     * @param string $originalFileName
     * @return Response
     */
    public function editAction(Request $request, $fileName, $originalFileName)
    {
        $form = $this->createForm(new AudioTagsEditType());

        $info = $this->getInfo($fileName);
        $this->setFormData($form, $info['comments']);
        $originalForm = clone $form; //hack stupid symfony

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {

                    $this->setTags($fileName, $form, $info);

                    $form = $originalForm;
                    $info = $this->getInfo($fileName);
                    $this->setFormData($form, $info['comments']);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:AudioTags:edit.html.twig', array(
            'form' => $form->createView(),
            'info' => $info,
            'originalFileName' => $originalFileName,
            'fileName' => $fileName,
        ));
    }


    /**
     * @param Form $form
     * @param array $tags
     */
    protected function setFormData(Form $form, array $tags)
    {
        $data = array(
            //'picture' => null,
            'title' => isset($tags['title'][0]) ? $tags['title'][0] : '',
            'album_artist' => isset($tags['album_artist'][0]) ? $tags['album_artist'][0] : (isset($tags['albumartist'][0]) ? $tags['albumartist'][0] : (isset($tags['band'][0]) ? $tags['band'][0] : '')),
            'artist' => isset($tags['artist'][0]) ? $tags['artist'][0] : '',
            'album' => isset($tags['album'][0]) ? $tags['album'][0] : '',
            'year' => isset($tags['year'][0]) ? $tags['year'][0] : (isset($tags['date'][0]) ? $tags['date'][0] : ''),
            'track_number' => isset($tags['track_number'][0]) ? $tags['track_number'][0] : (isset($tags['track'][0]) ? $tags['track'][0] : ''),
            'url_user' => isset($tags['url_user'][0]) ? $tags['url_user'][0] : '',
            'genre' => isset($tags['genre'][0]) ? $tags['genre'][0] : '',
            'comment' => isset($tags['comment'][0]) ? $tags['comment'][0] : '',
        );

        if (isset($tags['picture'][0])) {
            $data['picture'] = new FileContent(
                $tags['picture'][0]['data'],
                $tags['picture'][0]['image_mime'],
                'image'
            );
        }

        $form->setData($data);
    }


    /**
     * @param string $fileName
     *
     * @return array
     */
    protected function getInfo($fileName)
    {
        $getid3 = $this->get('getid3')->getId3();
        $info = $getid3->analyze($this->getFilePath($fileName));
        \getid3_lib::CopyTagsToComments($info);

        return $info;
    }

    /**
     * @param string $fileName
     * @param Form $form
     * @param array $info
     * @throws AudioException
     */
    protected function setTags($fileName, Form $form, array $info)
    {
        $data = $form->getData();
        $writer = $this->get('getid3')->getId3Writer();
        if ($data['remove_other_tags']) {
            $writer->remove_other_tags = true;
        }
        $writer->overwrite_tags = true;
        $writer->tagformats = $this->getAllowedTagFormats($info);
        $writer->filename = $this->getFilePath($fileName);

        $writer->tag_data = array(
            'title' => array($data['title']),

            'album_artist' => array($data['album_artist']),
            'albumartist' => array($data['album_artist']),
            'band' => array($data['album_artist']),

            'artist' => array($data['artist']),
            'album' => array($data['album']),

            'year' => array($data['year']),
            'date' => array($data['year']),

            'track_number' => array($data['track_number']),
            'track' => array($data['track_number']),

            'url_user' => array($data['url_user']),
            'genre' => array($data['genre']),
            'comment' => array($data['comment']),
            //'picture' => null,
        );


        $requestForm = $this->get('request')->get($form->getName());

        if (isset($info['comments']['picture'][0]) && (!isset($requestForm['picture']['file_url_delete']) || !$requestForm['picture']['file_url_delete'])) {
            $writer->tag_data['attached_picture'][0]['data']          = $info['comments']['picture'][0]['data'];
            $writer->tag_data['attached_picture'][0]['picturetypeid'] = 0;
            $writer->tag_data['attached_picture'][0]['description']   = 'image';
            $writer->tag_data['attached_picture'][0]['mime']          = $info['comments']['picture'][0]['image_mime'];
        }

        /** @var UploadedFile $picture */
        $picture = $data['picture'];
        if ($picture) {
            $writer->tag_data['attached_picture'][0]['data']          = file_get_contents($picture->getPathname());
            $writer->tag_data['attached_picture'][0]['picturetypeid'] = 0;
            $writer->tag_data['attached_picture'][0]['description']   = 'image';
            $writer->tag_data['attached_picture'][0]['mime']          = $picture->getMimeType();
        }

        if (!$writer->WriteTags()) {
            throw new AudioException($writer->errors);
        }
    }

    /**
     * @param array $info (поддерживаются mp3, mp2, mp1, riff, mpc, flac, real, ogg)
     * @return array
     */
    protected function getAllowedTagFormats(array $info)
    {
        switch ($info['fileformat']) {
            case 'mp3':
            case 'mp2':
            case 'mp1':
            case 'riff': // maybe not officially, but people do it anyway
                return array(/*'id3v1', 'id3v2.2',*/ 'id3v2.3', 'id3v2.4'/*, 'ape', 'lyrics3'*/);
                break;

            case 'mpc':
                return array('ape');
                break;

            case 'flac':
                return array('metaflac');
                break;

            case 'real':
                return array('real');
                break;

            case 'ogg':
                switch ($info['audio']['dataformat']) {
                    case 'flac':
                        // metaflac is not (yet) compatible with OggFLAC files
                        // $AllowedTagFormats = array('metaflac');
                        break;
                    case 'vorbis':
                        return array('vorbiscomment');
                        break;
                    default:
                        // metaflac is not (yet) compatible with Ogg files other than OggVorbis
                        break;
                }
                break;
        }

        return array();
    }


    /**
     * @param string $fileName
     * @param string $originalFileName
     * @return BinaryFileResponse
     */
    public function downloadAction($fileName, $originalFileName)
    {
        $file = new BinaryFileResponse($this->getFilePath($fileName));
        $file->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $originalFileName);

        return $file;
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function getFilePath($fileName)
    {
        return \AppKernel::getTmpDir() . DIRECTORY_SEPARATOR . $fileName;
    }
}
