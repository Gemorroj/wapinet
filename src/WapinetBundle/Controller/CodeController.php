<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Exception\ValidatorException;
use WapinetBundle\Form\Type\Code\CodeType;

class CodeController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('@Wapinet/Code/index.html.twig', array(
            'form' => $form->createView(),
            'result' => $hash
        ));
    }


    /**
     * @param array $data
     * @throws ValidatorException
     * @return string
     */
    protected function getCode(array $data)
    {
        $hash = $this->get('code');

        if (null !== $data['text']) {
            return $hash->convertString($data['algorithm'], $data['text']);
        }
        //if (null !== $data['file']) {
        //    return $hash->convertFile($algorithm, $data['file']);
        //}

        throw new ValidatorException('Не заполнено ни одного поля с конвертируемыми данными');
    }
}
