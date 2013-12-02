<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Archiver\AddType;

class ArchiverController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Archiver:index.html.twig');
    }


    public function createAction(Request $request)
    {
        $form = $this->createForm(new AddType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();


                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Archiver:create.html.twig', array(
            'form' => $form->createView()
        ));
    }

}
