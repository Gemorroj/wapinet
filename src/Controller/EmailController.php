<?php

namespace App\Controller;

use App\Form\Type\Email\EmailType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailController extends AbstractController
{
    public function indexAction(Request $request, MailerInterface $mailer): Response
    {
        $result = null;
        $form = $this->createForm(EmailType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $message = $this->makeEmail($data);
                    $message->getHeaders()->addTextHeader(
                        'Received',
                        'from user ['.\implode(', ', $request->getClientIps()).']'
                    );

                    $mailer->send($message);
                    $result = true;
                }
            }
        } catch (Exception $e) {
            $result = false;
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Email/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    private function getEmailFooter(): string
    {
        return "\r\n\r\n---\r\n".$this->getParameter('wapinet_email_footer');
    }

    private function makeEmail(array $data): Email
    {
        $email = (new Email())
            ->from($data['from'])
            ->to($data['to'])
            ->subject($data['subject'])
            ->text($data['message'].$this->getEmailFooter());

        if ($data['file'] instanceof UploadedFile) {
            $email->attachFromPath(
                $data['file']->getPathname(),
                $data['file']->getClientOriginalName(),
                $data['file']->getClientMimeType()
            );
        }

        return $email;
    }
}
