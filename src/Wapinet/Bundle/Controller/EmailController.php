<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Email\EmailType;

class EmailController extends Controller
{
    public function indexAction(Request $request)
    {
        //$message = \Swift_Message::newInstance();
        //$attachment = \Swift_Attachment::newInstance('contents file', 'my-file.pdf', 'application/pdf');
        //$attachment = \Swift_Attachment::fromPath('my-file.pdf', 'application/pdf');
        //$message->attach($attachment);

        $result = null;
        $form = $this->createForm(new EmailType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();
                //
            }
        }

        return $this->render('WapinetBundle:Email:index.html.twig', array(
            'form' => $form->createView(),
             'result' => $result
        ));
    }

}
