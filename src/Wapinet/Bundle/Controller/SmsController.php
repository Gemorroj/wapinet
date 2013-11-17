<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Sms\SmsType;

class SmsController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(new SmsType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $message = $this->send($data);
                    $result = (bool)$this->get('mailer')->send($message);
                } catch (\Exception $e) {
                    $form->addError(new FormError($e->getMessage()));
                }

            }
        }

        return $this->render('WapinetBundle:Sms:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result
        ));
    }

    /**
     * @param array $data
     * @return \Swift_Message
     */
    protected function send(array $data)
    {
        $message = \Swift_Message::newInstance($data['number'], $data['message'], 'text/plain', 'UTF-8');
        $message->setTo($data['number'] . '@' . $data['gateway']);
        $message->setFrom('robot@' . $data['gateway']);

        return $message;
    }
}
