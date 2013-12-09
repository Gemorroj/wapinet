<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Form\Type\File\UploadType;

/**
 * @see http://wap4file.org
 */
class FileController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:File:index.html.twig');
    }

    public function informationAction()
    {
        return $this->render('WapinetBundle:File:information.html.twig');
    }

    public function statisticsAction()
    {
        return $this->render('WapinetBundle:File:statistics.html.twig');
    }

    public function uploadAction(Request $request)
    {
        $form = $this->createForm(new UploadType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $file = $this->saveFile($data);

                    $router = $this->container->get('router');
                    return new RedirectResponse(
                        $router->generate('file_view', array(
                                'id' => $file->getId()
                            ), Router::ABSOLUTE_URL
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:File:upload.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @param array $data
     * @return File
     */
    protected function saveFile(array $data)
    {
        throw new \Exception('Не реализовано');
    }
}
