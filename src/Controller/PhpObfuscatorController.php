<?php

namespace App\Controller;

use App\Form\Type\Obfuscator\ObfuscatorType;
use App\Service\PhpObfuscator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/php_obfuscator')]
class PhpObfuscatorController extends AbstractController
{
    #[Route(path: '', name: 'php_obfuscator_index')]
    public function indexAction(Request $request, PhpObfuscator $phpObfuscator): Response
    {
        $result = null;
        $form = $this->createForm(ObfuscatorType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $phpObfuscator->obfuscate(
                        $data['code'] ?? '',
                        $data['remove_comments'] ?? false,
                        $data['remove_spaces'] ?? false
                    );
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('PhpObfuscator/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }
}
