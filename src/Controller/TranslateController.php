<?php

namespace App\Controller;

use App\Form\Type\Translate\TranslateType;
use App\Helper\Curl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TranslateController extends AbstractController
{
    /**
     * @param Request $request
     *
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

        return $this->render('Translate/index.html.twig', [
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
     * @throws HttpException
     *
     * @return string
     */
    private function translate($langFrom, $langTo, $text): string
    {
        $curl = $this->get('curl');
        $curl->init(
            'https://translate.yandex.net/api/v1.5/tr.json/translate?key='.
            $this->getParameter('wapinet_yandex_translate_key').
            '&lang='.$langFrom.'-'.$langTo.
            '&text='.\urlencode($text)
        );

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode(), 'Api error');
        }

        $json = \json_decode($response->getContent());

        return \implode('', $json->text);
    }

    /**
     * @param string $text
     *
     * @throws HttpException
     *
     * @return string
     */
    private function detectLang($text)
    {
        $curl = $this->get('curl');
        $curl->init(
            'https://translate.yandex.net/api/v1.5/tr.json/detect?key='.
            $this->getParameter('wapinet_yandex_translate_key').
            '&text='.\urlencode($text)
        );

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode(), 'Api error');
        }

        $json = \json_decode($response->getContent());

        return $json->lang ?: 'en';
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function getLangName($code)
    {
        $json = $this->getLangs();

        return (string) $json['langs'][$code];
    }

    /**
     * @throws \RuntimeException
     *
     * @return array
     */
    private function getLangs()
    {
        $cacheDir = $this->getParameter('kernel.cache_dir');
        $langsFileName = $cacheDir.\DIRECTORY_SEPARATOR.'yandex-langs.json';

        if (false === \file_exists($langsFileName)) {
            $curl = $this->get('curl');
            $curl->init(
                'https://translate.yandex.net/api/v1.5/tr.json/getLangs?key='.
                $this->getParameter('wapinet_yandex_translate_key').
                '&ui=ru'
            );

            $response = $curl->exec();
            if (!$response->isSuccessful()) {
                throw new HttpException($response->getStatusCode(), 'Api error');
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

        return \json_decode($langs, true);
    }

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();
        $services['curl'] = '?'.Curl::class;
        return $services;
    }
}
