<?php

namespace App\Controller;

use App\Form\Type\CssValidator\CssValidatorType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidatorException;

class CssValidatorController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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


    /**
     * @param array $data
     * @throws ValidatorException
     * @return \CSSValidator\Response
     */
    protected function getCssValidator(array $data)
    {
        $cssValidator = $this->get('css_validator');
        $options = $cssValidator->getOptions();

        $options->setProfile($data['profile']);
        $options->setWarning($data['warning']);
        $options->setUsermedium($data['usermedium']);

        if (null !== $data['css']) {
            return $cssValidator->validateFragment($data['css']);
        }
        if (null !== $data['file']) {
            return $cssValidator->validateFile($data['file']);
        }
        throw new ValidatorException('Не заполнено ни одного поля с CSS кодом ');
    }
}