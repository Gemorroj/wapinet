<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Hash\HashType;
use Symfony\Component\Form\FormError;

class HashController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $hash = null;
        $form = $this->createForm(new HashType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $algorithms = \hash_algos();
                    $algorithm = $algorithms[$data['algorithm']];

                    $hash = \hash($algorithm, $data['text']);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Hash:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $hash
        ));
    }
}
