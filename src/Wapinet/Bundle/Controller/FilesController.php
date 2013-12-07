<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Files\UploadType;

/**
 * @see http://wap4file.org
 */
class FilesController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Files:index.html.twig');
    }

    public function informationAction()
    {
        return $this->render('WapinetBundle:Files:information.html.twig');
    }

    public function statisticsAction()
    {
        return $this->render('WapinetBundle:Files:statistics.html.twig');
    }

    public function uploadAction(Request $request)
    {
        $form = $this->createForm(new UploadType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    throw new \Exception('Not implemented');
                    /*
                    $file = $this->saveFile($data);
                    $router = $this->container->get('router');

                    return new RedirectResponse(
                        $router->generate('audio_tags_edit', array(
                                'fileName' => $file->getBasename(),
                                'originalFileName' => $data['file']->getClientOriginalName()
                            ), Router::ABSOLUTE_URL
                        )
                    );
                    */
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Files:upload.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
