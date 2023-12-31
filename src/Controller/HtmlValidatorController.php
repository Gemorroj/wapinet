<?php

namespace App\Controller;

use App\Form\Type\HtmlValidator\HtmlValidatorType;
use App\Service\HtmlValidator;
use HTMLValidator\Response as HTMLValidatorResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

#[Route('/html_validator')]
class HtmlValidatorController extends AbstractController
{
    #[Route(path: '', name: 'html_validator_index')]
    public function indexAction(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(HtmlValidatorType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getHtmlValidator($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('HtmlValidator/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    private function getHtmlValidator(array $data): HTMLValidatorResponse
    {
        /** @var HtmlValidator $htmlValidator */
        $htmlValidator = $this->container->get(HtmlValidator::class);

        if (null !== $data['html']) {
            return $htmlValidator->validateFragment($data['html']);
        }
        if (null !== $data['file']) {
            return $htmlValidator->validateFile($data['file']);
        }
        throw new ValidatorException('Не заполнено ни одного поля с HTML кодом ');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[HtmlValidator::class] = '?'.HtmlValidator::class;

        return $services;
    }
}
