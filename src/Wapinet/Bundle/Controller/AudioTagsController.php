<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\AudioTags\AudioTagsType;
use Wapinet\Bundle\Form\Type\AudioTags\AudioTagsEditType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wapinet\Bundle\Entity\FileUrl;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

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
                        $router->generate('audio_tags_edit', array('fileName' => $file), Router::ABSOLUTE_URL)
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


    protected function saveFile(array $data)
    {
        return '';
    }


    public function editAction(Request $request, $fileName)
    {
        $result = null;
        $form = $this->createForm(new AudioTagsEditType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $result = $this->getTags($data);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:AudioTags:edit.html.twig', array(
                'form' => $form->createView(),
                'result' => $result
            ));
    }


    /**
     * @param array $data
     *
     * @return array
     */
    protected function getTags(array $data)
    {
        return array();
    }


    public function downloadAction(Request $request, $fileName)
    {
        $file = '';
        return new BinaryFileResponse($file);
    }
}
