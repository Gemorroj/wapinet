<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\VarDumper\VarDumper;
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
        $form = $this->createForm(new PagerankType());

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
        $url = \str_ireplace('http://', '', $data['url']);

        return array(
            'url' => $url,
            'google_pages' => $this->getGooglePages($url),
            'google_inurl' => $this->getGoogleInurl($url),
            'google_pr' => $this->getGooglePr($url),
            'yandex_pages' => $this->getYandexPages($url),
            'yandex_tcy' => $this->getYandexTcy($url),
        );
    }

    /**
     * @param string $url
     * @return string
     */
    protected function getYandexPages($url)
    {
        $curl = $this->get('curl');
        $curl->init('https://yandex.ru/search/xml?user=gemorwapinet&key=' . $this->container->getParameter('wapinet_search_key'));
        $curl->setOpt(\CURLOPT_POST, true);
        $curl->setOpt(\CURLOPT_POSTFIELDS, '<?xml version="1.0" encoding="UTF-8"?><request><query>' . \htmlspecialchars($url, \ENT_XML1) . '</query><groupings><groupby groups-on-page="1"/></groupings></request>');

        $out = 'N/A';
        try {
            $response = $curl->exec();

            if (\preg_match('/<found priority="all">(.*)<\/found>/U', $response->getContent(), $match)) {
                $out = $match[1];
            }
        } catch (\Exception $e) {}

        return $out;
    }


    /**
     * @param string $url
     * @return string
     */
    protected function getYandexTcy($url)
    {
        $curl = $this->get('curl');
        $curl->init('https://bar-navig.yandex.ru/u?ver=2&show=32&url=http://' . $url);
        $curl->addCompression();

        $out = 'N/A';
        try {
            $response = $curl->exec();

            if (\preg_match("/value=\"(.\d*)\"/", $response, $match)) {
                $out = $match[1];
            }
        } catch (\Exception $e) {}

        return $out;
    }


    /**
     * @param string $url
     * @return string
     */
    protected function getGooglePages($url)
    {
        $curl = $this->get('curl');
        $curl->init('http://www.google.com/search?hl=en&q=' . \rawurlencode('site:' . $url));
        $curl->acceptRedirects();
        $curl->addCompression();
        $curl->addHeader('Accept-Language', 'en-US,en');

        $out = 'N/A';
        try {
            $response = $curl->exec();
            /*
            <div id="resultStats">345,000 results</div>
            <div id="resultStats">About 345,000 results</div>
             */

            if (\strpos($response->getContent(), 'did not match any documents')) {
                $out = '0';
            } else {
                \preg_match('/<div id="resultStats">(.+?)<\/div>/', $response->getContent(), $match);
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
     * @param string $url
     * @return string
     */
    protected function getGoogleInurl($url)
    {
        $curl = $this->get('curl');
        $curl->init('http://www.google.com/search?hl=en&q=' . \rawurlencode('"' . $url . '" -inurl:"' . $url . '"'));
        $curl->acceptRedirects();
        $curl->addCompression();
        $curl->addHeader('Accept-Language', 'en-US,en');

        $out = 'N/A';
        try {
            $response = $curl->exec();
            /*
            <div id="resultStats">345,000 results</div>
            <div id="resultStats">About 345,000 results</div>
             */


            if (\strpos($response->getContent(), 'did not match any documents')) {
                $out = '0';
            } else {
                \preg_match('/<div id="resultStats">(.+?)<\/div>/', $response->getContent(), $match);
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
     * PageRank Lookup (Based on Google Toolbar for Mozilla Firefox)
     *
     * @copyright   2012 HM2K <hm2k@php.net>
     * @link        http://pagerank.phurix.net/
     * @author      James Wade <hm2k@php.net>
     * @version     $Revision: 2.1 $
     * @require     PHP 4.3.0 (file_get_contents)
     * @updated		06/10/11
     * @see         https://github.com/stfast/pagerank
     */
    protected function getGooglePr($q, $host = 'toolbarqueries.google.com', $context = null)
    {
        $seed = "Mining PageRank is AGAINST GOOGLE'S TERMS OF SERVICE. Yes, I'm talking to you, scammer.";
        $result = 0x01020345;
        $len = strlen($q);
        for ($i=0; $i<$len; $i++) {
            $result ^= ord($seed{$i%strlen($seed)}) ^ ord($q{$i});
            $result = (($result >> 23) & 0x1ff) | $result << 9;
        }
        if (PHP_INT_MAX != 2147483647) { $result = -(~($result & 0xFFFFFFFF) + 1); }
        $ch=sprintf('8%x', $result);
        $url='http://%s/tbr?client=navclient-auto&ch=%s&features=Rank&q=info:%s';
        $url=sprintf($url,$host,$ch,$q);
        @$pr=file_get_contents($url,false,$context);
        return $pr?substr(strrchr($pr, ':'), 1):false;
    }
}
