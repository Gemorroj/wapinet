<?php

namespace Wapinet\Bundle\Controller;

use SEOstats\Services\Google;
use SEOstats\Services\Social;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Pagerank\PagerankType;
use Symfony\Component\Form\FormError;


class PagerankController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
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
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Pagerank:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getPagerank(array $data)
    {
        $url = $data['url'];
        $domain = \str_ireplace(array('http://', 'https://'), '', $url);

        return array(
            'domain' => $domain,
            'google' => array(
                'pages' => $this->getGooglePages($domain),
                'inurl' => $this->getGoogleInurl($domain),
            ),
            'yandex' => array(
                'pages' => $this->getYandexPages($domain),
                'tcy' => $this->getYandexTcy($domain),
            ),
            'facebook' => Social::getFacebookShares($url),
            'vk' => Social::getVKontakteShares($url),
        );
    }

    /**
     * @param string $domain
     * @return string
     */
    private function getYandexPages($domain)
    {
        $curl = $this->get('curl');
        $curl->init('https://yandex.ru/search/xml?user=gemorwapinet&key=' . $this->getParameter('wapinet_search_key'));
        $curl->setOpt(\CURLOPT_POST, true);
        $curl->setOpt(\CURLOPT_POSTFIELDS, '<?xml version="1.0" encoding="UTF-8"?><request><query>' . \htmlspecialchars($domain, \ENT_XML1) . '</query><groupings><groupby groups-on-page="1"/></groupings></request>');

        $out = 'n.a.';
        try {
            $response = $curl->exec();

            if (\preg_match('/<found priority="all">(.*)<\/found>/U', $response->getContent(), $match)) {
                $out = $match[1];
            }
        } catch (\Exception $e) {}

        return $out;
    }


    /**
     * @param string $domain
     * @return string
     */
    private function getYandexTcy($domain)
    {
        $curl = $this->get('curl');
        $curl->init('https://bar-navig.yandex.ru/u?ver=2&show=32&url=http://' . $domain);
        $curl->addCompression();

        $out = 'n.a.';
        try {
            $response = $curl->exec();

            if (\preg_match("/value=\"(.\d*)\"/", $response, $match)) {
                $out = $match[1];
            }
        } catch (\Exception $e) {}

        return $out;
    }


    /**
     * @param string $domain
     * @return string
     */
    protected function getGooglePages($domain)
    {
        //return Google::getSiteindexTotal($domain);

        $curl = $this->get('curl');
        $curl->init('https://www.google.com/search?hl=en&q=' . \rawurlencode('site:' . $domain));
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

            if (\strpos($response->getContent(), 'did not match any documents')) {
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

        } catch (\Exception $e) {}

        return $out;
    }


    /**
     * @param string $domain
     * @return string
     */
    protected function getGoogleInurl($domain)
    {
        //return Google::getBacklinksTotal($domain);

        $curl = $this->get('curl');
        $curl->init('https://www.google.com/search?hl=en&q=' . \rawurlencode('"' . $domain . '" -inurl:"' . $domain . '"'));
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


            if (\strpos($response->getContent(), 'did not match any documents')) {
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

        } catch (\Exception $e) {}

        return $out;
    }
}
