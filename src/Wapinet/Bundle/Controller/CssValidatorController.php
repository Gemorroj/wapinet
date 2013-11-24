<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\CssValidator\CssValidatorType;

class CssValidatorController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(new CssValidatorType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $result = $this->getCssValidator($data);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:CssValidator:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }


    /**
     * @param array $data
     * @return \Services_W3C_CSSValidator_Response
     */
    protected function getCssValidator(array $data)
    {
        $cssValidator = $this->get('css_validator');
        $cssValidator->setProfile($data['profile']);
        $cssValidator->setWarning($data['warning']);
        $cssValidator->setUsermedium($data['usermedium']);

        return $cssValidator->validateFragment($data['css']);
    }
}
