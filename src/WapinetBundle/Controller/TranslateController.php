<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WapinetBundle\Form\Type\Translate\TranslateType;

class TranslateController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $result = null;
        $detectLangName = null;
        $form = $this->createForm(TranslateType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();

                    if ('auto' === $data['lang_from']) {
                        $langFrom = $this->detectLang($data['text']);
                        $detectLangName = $this->getLangName($langFrom);
                    } else {
                        $langFrom = $data['lang_from'];
                    }

                    $result = $this->translate($langFrom, $data['lang_to'], $data['text']);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('@Wapinet/Translate/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
            'detectLangName' => $detectLangName,
        ]);
    }


    /**
     * @param string $langFrom
     * @param string $langTo
     * @param string $text
     *
     * @return string
     * @throws HttpException
     */
    private function translate($langFrom, $langTo, $text)
    {
        $curl = $this->get('curl');
        $curl->init(
            'https://translate.yandex.net/api/v1.5/tr.json/translate?key=' .
            $this->getParameter('wapinet_translate_key') .
            '&lang=' . $langFrom . '-' . $langTo .
            '&text=' . \urlencode($text)
        );

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode());
        }

        $json = \json_decode($response->getContent());

        return \implode('', $json->text);
    }


    /**
     * @param string $text
     *
     * @return string
     * @throws HttpException
     */
    private function detectLang($text)
    {
        $curl = $this->get('curl');
        $curl->init(
            'https://translate.yandex.net/api/v1.5/tr.json/detect?key=' .
            $this->getParameter('wapinet_translate_key') .
            '&text=' . \urlencode($text)
        );

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode());
        }

        $json = \json_decode($response->getContent());

        return ($json->lang ?: 'en');
    }


    /**
     * @param string $code
     * @return string
     */
    private function getLangName($code)
    {
        $json = $this->getLangs();

        return (string)$json->langs->{$code};
    }


    /**
     * @return \stdClass
     * @throws \RuntimeException
     */
    private function getLangs()
    {
        $cacheDir = $this->get('kernel')->getCacheDir();
        $langsFileName = $cacheDir . \DIRECTORY_SEPARATOR . 'yandex-langs.json';

        if (false === \file_exists($langsFileName)) {
            $curl = $this->get('curl');
            $curl->init(
                'https://translate.yandex.net/api/v1.5/tr.json/getLangs?key=' .
                $this->getParameter('wapinet_translate_key') .
                '&ui=ru'
            );

            $response = $curl->exec();
            if (!$response->isSuccessful()) {
                throw new HttpException($response->getStatusCode());
            }

            $langs = $response->getContent();

            $result = \file_put_contents($langsFileName, $langs);
            if (false === $result) {
                throw new \RuntimeException('Не удалось записать языки перевода');
            }
        } else {
            $langs = \file_get_contents($langsFileName);
            if (false === $langs) {
                throw new \RuntimeException('Не удалось прочитать языки перевода');
            }
        }

        return \json_decode($langs);
    }
}
