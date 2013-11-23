<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $file = $this->saveFile($data);
                    $router = $this->container->get('router');

                    return new RedirectResponse(
                        $router->generate('audio_tags_edit', array(
                                'fileName' => $file->getBasename(),
                                'originalFileName' => $data['file']->getClientOriginalName()
                            ), Router::ABSOLUTE_URL
                        )
                    );
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }
            }
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
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $this->setTags($fileName, $data);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        $info = $this->getInfo($fileName);

        if (isset($info['comments']['picture'][0])) {
            $form->setData(array(
                'picture' => new FileContent(
                    $info['comments']['picture'][0]['data'],
                    $info['comments']['picture'][0]['image_mime'],
                    'Картинка'
                )
            ));
        }

        return $this->render('WapinetBundle:AudioTags:edit.html.twig', array(
            'form' => $form->createView(),
            'tags' => $info['comments'],
            'info' => $info['audio'],
            'originalFileName' => $originalFileName,
            'fileName' => $fileName,
        ));
    }


    /**
     * @param string $fileName
     *
     * @return array
     */
    protected function getInfo($fileName)
    {
        $getid3 = $this->get('getid3')->getId3();
        $info = $getid3->analyze(\AppKernel::getTmpDir() . DIRECTORY_SEPARATOR . $fileName);
        \getid3_lib::CopyTagsToComments($info);

        return $info;
    }

    /**
     * @param string $fileName
     * @param array $data
     * @throws \RuntimeException
     */
    protected function setTags($fileName, array $data)
    {
        file_put_contents('/log.log', print_r($data, true), FILE_APPEND);
    }


    /**
     * @param Request $request
     * @param string $fileName
     * @param string $originalFileName
     * @return BinaryFileResponse
     */
    public function downloadAction(Request $request, $fileName, $originalFileName)
    {
        $file = new BinaryFileResponse(\AppKernel::getTmpDir() . DIRECTORY_SEPARATOR . $fileName);
        $file->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $originalFileName);

        return $file;
    }
}
