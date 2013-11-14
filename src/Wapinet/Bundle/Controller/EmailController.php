<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Email\EmailType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
                file_put_contents('/log.log', print_r($data, true));

                $message = \Swift_Message::newInstance($data['subject'], $data['message'], 'text/plain', 'UTF-8');

                if ($data['attach'] instanceof UploadedFile) {

                } elseif ('' !== $data['url']) {

                }
            }
        }

        return $this->render('WapinetBundle:Email:index.html.twig', array(
            'form' => $form->createView(),
             'result' => $result
        ));
    }

}
