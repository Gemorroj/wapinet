<?php

namespace App\Controller;

use App\Form\Type\Code\CodeType;
use App\Helper\Code;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;

class CodeController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $hash = null;
        $form = $this->createForm(CodeType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $hash = $this->getCode($data);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Code/index.html.twig', [
            'form' => $form->createView(),
            'result' => $hash,
        ]);
    }

    protected function getCode(array $data): string
    {
        /** @var Code $code */
        $code = $this->get(Code::class);

        if (null !== $data['text']) {
            return $code->convertString($data['algorithm'], $data['text']);
        }
        //if (null !== $data['file']) {
        //    return $code->convertFile($algorithm, $data['file']);
        //}

        throw new ValidatorException('Не заполнено ни одного поля с конвертируемыми данными');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Code::class] = '?'.Code::class;

        return $services;
    }
}
