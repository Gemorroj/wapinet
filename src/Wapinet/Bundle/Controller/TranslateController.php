<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Wapinet\Bundle\Form\Type\Translate\TranslateType;
use Symfony\Component\Form\FormError;

// TODO: https://github.com/nkt/yandex-translate
class TranslateController extends Controller
{
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(new TranslateType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getTranslate($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Translate:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }


    /**
     * @param array $data
     *
     * @return string
     * @throws HttpException
     */
    protected function getTranslate(array $data)
    {
        $curl = $this->get('curl');
        $curl->setOpt(CURLOPT_URL,
            'https://translate.yandex.net/api/v1.5/tr.json/translate?key=' .
            $this->container->getParameter('wapinet_translate_key') .
            '&lang=' . $data['lang'] .
            '&text=' . urlencode($data['text'])
        );

        $response = $curl->exec();

        if (200 !== $response->getStatusCode()) {
            throw new HttpException($response->getStatusCode());
        }

        $json = json_decode($response->getContent());

        return implode('', $json->text);
    }
}
