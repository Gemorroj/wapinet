<?php

namespace App\Controller;

use App\Form\Type\Email\EmailType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/email')]
class EmailController extends AbstractController
{
    #[Route(path: '', name: 'email_index')]
    public function indexAction(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $result = null;
        $form = $this->createForm(EmailType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $message = $this->makeEmail($data, $request->getHost());
                    $message->getHeaders()->addTextHeader(
                        'Received',
                        'from user ['.\implode(', ', $request->getClientIps()).']'
                    );

                    $mailer->send($message);
                    $result = true;
                }
            }
        } catch (\Exception $e) {
            $logger->warning('Не удалось отправить email', [
                'exception' => $e,
                'email' => $message ?? null,
                'request' => $request,
            ]);
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

    private function makeEmail(array $data, string $host): Email
    {
        $email = (new Email())
            ->from($data['from'].'@'.$host)
            ->to($data['to'])
            ->subject($data['subject'])
            ->text($data['message'].$this->getEmailFooter());

        if ($data['file'] instanceof UploadedFile) {
            if (false === $handle = @\fopen($data['file']->getPathname(), 'r', false)) {
                throw new \RuntimeException('Unable to open file.');
            }
            $email->attach($handle, $data['file']->getClientOriginalName(), $data['file']->getClientMimeType());
            /*$email->attachFromPath(
                $data['file']->getPathname(),
                $data['file']->getClientOriginalName(),
                $data['file']->getClientMimeType()
            );*/
        }

        return $email;
    }
}
