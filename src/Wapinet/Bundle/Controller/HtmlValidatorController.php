<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidatorException;
use Wapinet\Bundle\Form\Type\HtmlValidator\HtmlValidatorType;

class HtmlValidatorController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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

        return $this->render('WapinetBundle:HtmlValidator:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }


    /**
     * @param array $data
     * @throws ValidatorException
     * @return \HTMLValidator\Response
     */
    protected function getHtmlValidator(array $data)
    {
        $htmlValidator = $this->get('html_validator');
        if (null !== $data['html']) {
            return $htmlValidator->validateFragment($data['html']);
        }
        if (null !== $data['file']) {
            return $htmlValidator->validateFile($data['file']);
        }
        throw new ValidatorException('Не заполнено ни одного поля с HTML кодом ');
    }
}
