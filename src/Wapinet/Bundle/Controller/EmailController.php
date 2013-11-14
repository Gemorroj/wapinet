<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Email\EmailType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormError;

class EmailController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(new EmailType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $message = $this->getMessage($data);
                    $result = (bool)$this->get('mailer')->send($message);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:Email:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result
        ));
    }


    /**
     * @param array $data
     *
     * @return \Swift_Message
     */
    protected function getMessage(array $data)
    {
        $message = \Swift_Message::newInstance($data['subject'], $data['message'], 'text/plain', 'UTF-8');
        $message->setFrom($data['from']);
        $message->setTo($data['to']);

        if ($data['attach'] instanceof UploadedFile) {
            $attach = \Swift_Attachment::fromPath($data['attach']->getPathname(), $data['attach']->getClientMimeType());
            $attach->setFilename($data['attach']->getClientOriginalName());
            $message->attach($attach);
        } elseif (null !== $data['url']) {
            $curl = $this->get('curl_helper');
            $curl->setOpt(CURLOPT_URL, $data['url']);
            $curl->addBrowserHeaders();

            $curl->checkFileSize();

            $response = $curl->exec();
            $attach = \Swift_Attachment::newInstance($response->getContent(), basename($data['url']), $response->headers->get('Content-Type'));
            $message->attach($attach);
        }

        return $message;
    }
}
