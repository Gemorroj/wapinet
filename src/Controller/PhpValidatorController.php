<?php

namespace App\Controller;

use App\Form\Type\PhpValidator\PhpValidatorType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidatorException;

class PhpValidatorController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('PhpValidator/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    /**
     * @param array $data
     *
     * @throws ValidatorException
     *
     * @return array
     */
    protected function getPhpValidator(array $data)
    {
        $phpValidator = $this->get('php_validator');
        if (null !== $data['php']) {
            return $phpValidator->validateFragment($data['php']);
        }
        if (null !== $data['file']) {
            return $phpValidator->validateFile($data['file']);
        }
        throw new ValidatorException('Не заполнено ни одного поля с PHP кодом');
    }
}
