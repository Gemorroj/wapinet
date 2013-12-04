<?php
namespace Wapinet\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\UserBundle\Form\Type\MenuType;

class MenuController extends Controller
{
    public function editAction(Request $request)
    {
        $form = $this->createForm(new MenuType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    throw new \Exception('Not implemented');
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetUserBundle:Menu:edit.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
