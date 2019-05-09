<?php

namespace App\Controller;

use App\Form\Type\Email\EmailType;
use Exception;
use function implode;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailController extends AbstractController
{
    public function indexAction(Request $request, Swift_Mailer $mailer): Response
    {
        $result = null;
        $form = $this->createForm(EmailType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $message = $this->makeMessage($data);
                    $message->getHeaders()->addTextHeader(
                        'Received',
                        'from user ['. implode(', ', $request->getClientIps()).']'
                    );

                    $result = (bool) $mailer->send($message);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Email/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    protected function getEmailFooter(): string
    {
        return "\r\n\r\n---\r\n".$this->getParameter('wapinet_email_footer');
    }

    protected function makeMessage(array $data): Swift_Message
    {
        $message = new Swift_Message(
            $data['subject'],
            $data['message'].$this->getEmailFooter(),
            'text/plain',
            'UTF-8'
        );
        $message->setFrom($data['from']);
        $message->setTo($data['to']);

        if ($data['file'] instanceof UploadedFile) {
            $attach = Swift_Attachment::fromPath($data['file']->getPathname(), $data['file']->getClientMimeType());
            $attach->setFilename($data['file']->getClientOriginalName());
            $message->attach($attach);
        }

        return $message;
    }
}
