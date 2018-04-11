<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use WapinetBundle\Form\Type\Email\EmailType;

class EmailController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(EmailType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $message = $this->getMessage($data);
                    $result = (bool)$this->get('mailer')->send($message);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('@Wapinet/Email/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result
        ]);
    }


    /**
     * @return string
     */
    protected function getEmailFooter()
    {
        return "\r\n\r\n---\r\n" . $this->getParameter('wapinet_email_footer');
    }


    /**
     * @param array $data
     *
     * @return \Swift_Message
     */
    protected function getMessage(array $data)
    {
        $message = \Swift_Message::newInstance(
            $data['subject'],
            $data['message'] . $this->getEmailFooter(),
            'text/plain',
            'UTF-8'
        );
        $message->setFrom($data['from']);
        $message->setTo($data['to']);

        if ($data['file'] instanceof UploadedFile) {
            $attach = \Swift_Attachment::fromPath($data['file']->getPathname(), $data['file']->getClientMimeType());
            $attach->setFilename($data['file']->getClientOriginalName());
            $message->attach($attach);
        }

        $request = $this->get('request_stack')->getCurrentRequest();
        $ip = \implode(', ', $request->getClientIps());

        $message->getHeaders()->addTextHeader('Received', 'from user [' . $ip . ']');

        return $message;
    }
}
