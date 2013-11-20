<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Rename\RenameType;
use Wapinet\Bundle\Entity\File;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class RenameController extends Controller
{
    /**
     * @param Request $request
     * @return Response|BinaryFileResponse
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new RenameType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    return $this->getRename($data);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:Rename:index.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @param array $data
     * @return BinaryFileResponse
     * @throws \RuntimeException
     */
    protected function getRename(array $data)
    {
        if ($data['file'] instanceof File) {
            $file = new BinaryFileResponse($data['file']->getUploadedFile());
            $file->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $data['name']);
            return $file;
        }

        throw new \RuntimeException('Не указан файл');
    }
}
