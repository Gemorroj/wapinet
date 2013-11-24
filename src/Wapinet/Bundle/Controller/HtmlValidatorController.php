<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\HtmlValidator\HtmlValidatorType;

class HtmlValidatorController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(new HtmlValidatorType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $result = $this->getHtmlValidator($data);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:HtmlValidator:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }


    /**
     * @param array $data
     * @return \Services_W3C_HTMLValidator
     */
    protected function getHtmlValidator(array $data)
    {
        $htmlValidator = $this->get('html_validator');
        return $htmlValidator->validateFragment($data['html']);
    }
}
