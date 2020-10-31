<?php

namespace App\Controller;

use App\Form\Type\PhpValidator\PhpValidatorType;
use App\Service\PhpValidator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/php_validator")
 */
class PhpValidatorController extends AbstractController
{
    /**
     * @Route("", name="php_validator_index")
     */
    public function indexAction(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(PhpValidatorType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getPhpValidator($data);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('PhpValidator/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    protected function getPhpValidator(array $data): array
    {
        /** @var PhpValidator $phpValidator */
        $phpValidator = $this->get(PhpValidator::class);

        if (null !== $data['php']) {
            return $phpValidator->validateFragment($data['php']);
        }
        if (null !== $data['file']) {
            return $phpValidator->validateFile($data['file']);
        }

        throw new ValidatorException('Не заполнено ни одного поля с PHP кодом');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[PhpValidator::class] = '?'.PhpValidator::class;

        return $services;
    }
}
