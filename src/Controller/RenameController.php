<?php

namespace App\Controller;

use App\Form\Type\Rename\RenameType;
use App\Service\Translit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rename')]
class RenameController extends AbstractController
{
    #[Route(path: '', name: 'rename_index')]
    public function indexAction(Request $request): Response
    {
        $form = $this->createForm(RenameType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    return $this->getRename($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Rename/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function getRename(array $data): BinaryFileResponse
    {
        if ($data['file'] instanceof UploadedFile) {
            $file = new BinaryFileResponse($data['file']);

            $file->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $data['name'],
                $this->container->get(Translit::class)->toAscii($data['name'])
            );

            return $file;
        }

        throw new \RuntimeException('Не указан файл');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Translit::class] = '?'.Translit::class;

        return $services;
    }
}
