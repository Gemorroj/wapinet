<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\Bundle\Form\Type\AudioTags\AudioTagsType;
use Wapinet\Bundle\Form\Type\AudioTags\AudioTagsEditType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wapinet\Bundle\Entity\FileUrl;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
        /** @var $file UploadedFile|FileUrl */
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
        $tags = $this->getTags($fileName);
        $form = $this->createForm(new AudioTagsEditType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $tags = $this->setTags($fileName, $data);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:AudioTags:edit.html.twig', array(
            'form' => $form->createView(),
            'tags' => $tags,
            'originalFileName' => $originalFileName,
            'fileName' => $fileName,
        ));
    }


    /**
     * @param string $fileName
     *
     * @return array
     */
    protected function getTags($fileName)
    {
        $getid3 = $this->get('getid3')->getId3();
        return $getid3->analyze(\AppKernel::getTmpDir() . DIRECTORY_SEPARATOR . $fileName);
    }

    /**
     * @param string $fileName
     * @param array $data
     *
     * @return array
     */
    protected function setTags($fileName, array $data)
    {
        return array();
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