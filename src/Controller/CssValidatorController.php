<?php

namespace App\Controller;

use App\Form\Type\CssValidator\CssValidatorType;
use App\Service\CssValidator;
use CSSValidator\Response as CSSValidatorResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

#[Route('/css_validator')]
class CssValidatorController extends AbstractController
{
    #[Route(path: '', name: 'css_validator_index')]
    public function indexAction(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(CssValidatorType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $result = $this->getCssValidator($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('CssValidator/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    private function getCssValidator(array $data): CSSValidatorResponse
    {
        /** @var CssValidator $cssValidator */
        $cssValidator = $this->container->get(CssValidator::class);
        $options = $cssValidator->getOptions();

        $options->setProfile($data['profile']);
        $options->setWarning($data['warning']);

        if (null !== $data['css']) {
            return $cssValidator->validateFragment($data['css']);
        }
        if (null !== $data['file']) {
            return $cssValidator->validateFile($data['file']);
        }
        throw new ValidatorException('Не заполнено ни одного поля с CSS кодом ');
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[CssValidator::class] = '?'.CssValidator::class;

        return $services;
    }
}
