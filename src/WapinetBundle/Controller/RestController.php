<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WapinetBundle\Form\Type\Rest\RestType;

class RestController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(RestType::class);
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getRest($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Rest:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }


    /**
     * @param array $data
     * @return Response
     */
    protected function getRest(array $data)
    {
        $curl = $this->get('curl');
        $curl->init($data['url']);
        $curl->setOpt(\CURLOPT_CUSTOMREQUEST, $data['type']);

        if (null !== $data['header']) {
            foreach (\explode("\n", \str_replace("\r", '', \trim($data['header']))) as $header) {
                list($key, $value) = \explode(':', $header, 2);
                $curl->addHeader($key, $value);
            }
        }

        if (null !== $data['body']) {
            $curl->setOpt(\CURLOPT_POSTFIELDS, $data['body']);
        }

        return $curl->exec();
    }
}
