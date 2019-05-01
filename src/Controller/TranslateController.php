<?php

namespace App\Controller;

use App\Form\Type\Translate\TranslateType;
use App\Helper\Curl;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function json_decode;
use function urlencode;
use const DIRECTORY_SEPARATOR;

class TranslateController extends AbstractController
{
    public function indexAction(Request $request): Response
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
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Translate/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
            'detectLangName' => $detectLangName,
        ]);
    }

    private function translate(string $langFrom, string $langTo, string $text): string
    {
        /** @var Curl $curl */
        $curl = $this->get(Curl::class);
        $curl->init(
            'https://translate.yandex.net/api/v1.5/tr.json/translate?key='.
            $this->getParameter('wapinet_yandex_translate_key').
            '&lang='.$langFrom.'-'.$langTo.
            '&text='. urlencode($text)
        );

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode(), 'Api error');
        }

        $json = json_decode($response->getContent());

        return implode('', $json->text);
    }

    private function detectLang(string $text): string
    {
        /** @var Curl $curl */
        $curl = $this->get(Curl::class);
        $curl->init(
            'https://translate.yandex.net/api/v1.5/tr.json/detect?key='.
            $this->getParameter('wapinet_yandex_translate_key').
            '&text='. urlencode($text)
        );

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode(), 'Api error');
        }

        $json = json_decode($response->getContent(), false);

        return $json->lang ?: 'en';
    }

    private function getLangName(string $code): string
    {
        $json = $this->getLangs();

        return (string) $json['langs'][$code];
    }

    private function getLangs(): array
    {
        $cacheDir = $this->getParameter('kernel.cache_dir');
        $langsFileName = $cacheDir.DIRECTORY_SEPARATOR.'yandex-langs.json';

        if (false === file_exists($langsFileName)) {
            /** @var Curl $curl */
            $curl = $this->get(Curl::class);
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

            $result = file_put_contents($langsFileName, $langs);
            if (false === $result) {
                throw new RuntimeException('Не удалось записать языки перевода');
            }
        } else {
            $langs = file_get_contents($langsFileName);
            if (false === $langs) {
                throw new RuntimeException('Не удалось прочитать языки перевода');
            }
        }

        return json_decode($langs, true);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Curl::class] = '?'.Curl::class;

        return $services;
    }
}
