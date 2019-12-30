<?php

namespace App\Controller;

use App\Form\Type\Pagerank\PagerankType;
use App\Service\Curl;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const ENT_XML1;
use Exception;
use SEOstats\Services\Social;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PagerankController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(PagerankType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getPagerank($data);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Pagerank/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    private function getPagerank(array $data): array
    {
        $url = $data['url'];
        $domain = \str_ireplace(['http://', 'https://'], '', $url);

        return [
            'domain' => $domain,
            'google' => [
                'pages' => $this->getGooglePages($domain),
                'inurl' => $this->getGoogleInurl($domain),
            ],
            'yandex' => [
                'pages' => $this->getYandexPages($domain),
                'tcy' => $this->getYandexTcy($domain),
            ],
            'facebook' => Social::getFacebookShares($url),
            'vk' => Social::getVKontakteShares($url),
        ];
    }

    private function getYandexPages(string $domain): string
    {
        /** @var Curl $curl */
        $curl = $this->get(Curl::class);
        $curl->init('https://yandex.ru/search/xml?user=gemorwapinet&key='.$this->getParameter('wapinet_yandex_search_key'));
        $curl->setOpt(CURLOPT_POST, true);
        $curl->setOpt(CURLOPT_POSTFIELDS, '<?xml version="1.0" encoding="UTF-8"?><request><query>'.\htmlspecialchars($domain, ENT_XML1).'</query><groupings><groupby groups-on-page="1"/></groupings></request>');

        $out = 'n.a.';
        try {
            $response = $curl->exec();

            if (\preg_match('/<found priority="all">(.*)<\/found>/U', $response->getContent(), $match)) {
                $out = $match[1];
            }
        } catch (Exception $e) {
        }

        return $out;
    }

    private function getYandexTcy(string $domain): string
    {
        /** @var Curl $curl */
        $curl = $this->get(Curl::class);
        $curl->init('https://bar-navig.yandex.ru/u?ver=2&show=32&url=http://'.$domain);
        $curl->addCompression();

        $out = 'n.a.';
        try {
            $response = $curl->exec();

            if (\preg_match("/value=\"(.\d*)\"/", $response, $match)) {
                $out = $match[1];
            }
        } catch (Exception $e) {
        }

        return $out;
    }

    private function getGooglePages(string $domain): string
    {
        //return Google::getSiteindexTotal($domain);

        /** @var Curl $curl */
        $curl = $this->get(Curl::class);
        $curl->init('https://www.google.com/search?hl=en&q='.\rawurlencode('site:'.$domain));
        $curl->acceptRedirects();
        $curl->addCompression();
        $curl->addHeader('Accept-Language', 'en-US,en');

        $out = 'n.a.';
        try {
            $response = $curl->exec();
            /*
            <div id="resultStats">345,000 results</div>
            <div id="resultStats">About 345,000 results</div>
            <div class="sd" id="resultStats">About 120,000 results</div>
             */

            if (\mb_strpos($response->getContent(), 'did not match any documents')) {
                $out = '0';
            } else {
                \preg_match('/<div(?:.+)id="resultStats">(.+?)<\/div>/', $response->getContent(), $match);
                if (isset($match[1])) {
                    $match[1] = \str_replace('About ', '', $match[1]);
                    $match[1] = \str_replace(' results', '', $match[1]);

                    $match[1] = \preg_replace('/<nobr>(?:.*)<\/nobr>/', '', $match[1]);

                    $out = $match[1];
                }
            }
        } catch (Exception $e) {
        }

        return $out;
    }

    private function getGoogleInurl(string $domain): string
    {
        //return Google::getBacklinksTotal($domain);

        /** @var Curl $curl */
        $curl = $this->get(Curl::class);
        $curl->init('https://www.google.com/search?hl=en&q='.\rawurlencode('"'.$domain.'" -inurl:"'.$domain.'"'));
        $curl->acceptRedirects();
        $curl->addCompression();
        $curl->addHeader('Accept-Language', 'en-US,en');

        $out = 'n.a.';
        try {
            $response = $curl->exec();
            /*
            <div id="resultStats">345,000 results</div>
            <div id="resultStats">About 345,000 results</div>
            <div class="sd" id="resultStats">About 7,720 results</div>
             */

            if (\mb_strpos($response->getContent(), 'did not match any documents')) {
                $out = '0';
            } else {
                \preg_match('/<div(?:.+)id="resultStats">(.+?)<\/div>/', $response->getContent(), $match);
                if (isset($match[1])) {
                    $match[1] = \str_replace('About ', '', $match[1]);
                    $match[1] = \str_replace(' results', '', $match[1]);

                    $match[1] = \preg_replace('/<nobr>(?:.*)<\/nobr>/', '', $match[1]);

                    $out = $match[1];
                }
            }
        } catch (Exception $e) {
        }

        return $out;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Curl::class] = '?'.Curl::class;

        return $services;
    }
}
