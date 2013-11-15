<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BrowserInfoController extends Controller
{
    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getPhoneNumber(Request $request)
    {
        if ($request->server->has('HTTP_X_NETWORK_INFO')) {
            return preg_replace('/(.*,)(11[d])(,.*)/i', '$2', $request->server->get('HTTP_X_NETWORK_INFO'));
        }
        if ($request->server->has('HTTP_X_UP_CALLING_LINE_ID')) {
            return $request->server->get('HTTP_X_UP_CALLING_LINE_ID');
        }
        if ($request->server->has('HTTP_X_UP_SUBNO')) {
            return preg_replace('/(.*)(11[d])(.*)/i', '$2', $request->server->get('HTTP_X_UP_SUBNO'));
        }
        if ($request->server->has('DEVICEID')) {
            return $request->server->get('DEVICEID');
        }
        if ($request->server->has('HTTP_X_MSISDN')) {
            return $request->server->get('HTTP_X_MSISDN');
        }
        if ($request->server->has('HTTP_X_NOKIA_MSISDN')) {
            return $request->server->get('HTTP_X_NOKIA_MSISDN');
        }

        return '';
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getProxy(Request $request)
    {
        if ($request->server->has('HTTP_X_FORWARDED_FOR')) {
            return $request->server->get('HTTP_X_FORWARDED_FOR');
        }
        if ($request->server->has('HTTP_CLIENT_IP')) {
            return $request->server->get('HTTP_CLIENT_IP');
        }

        return '';
    }


    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getProxyHost(Request $request)
    {
        $proxy = $this->getProxy($request);
        if ('' !== $proxy) {
            $proxyHost = array();
            foreach(explode(',', $proxy) as $v) {
                $proxyHost[] = gethostbyaddr(trim($v));
            }
            return implode(',', $proxyHost);
        }

        return '';
    }


    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getEncoding(Request $request)
    {
        if ($request->server->has('HTTP_ACCEPT_ENCODING')) {
            return $request->server->get('HTTP_ACCEPT_ENCODING');
        }
        if ($request->server->has('HTTP_TE')) {
            return $request->server->get('HTTP_TE');
        }

        return '';
    }


    public function indexAction(Request $request)
    {
        return $this->render('WapinetBundle:BrowserInfo:index.html.twig', array(
                'userAgent' => $request->server->get('HTTP_USER_AGENT'),
                'wapProfile' => $request->server->get('HTTP_X_WAP_PROFILE'),
                'phoneNumber' => $this->getPhoneNumber($request),
                'ip' => $request->server->get('REMOTE_ADDR'),
                'ipHost' => gethostbyaddr($request->server->get('REMOTE_ADDR')),
                'local' => $this->getProxy($request),
                'localHost' => $this->getProxyHost($request),
                'port' => $request->server->get('REMOTE_PORT'),
                'referer' => $request->server->get('HTTP_REFERER'),
                'encoding' => $this->getEncoding($request),
                'cache' => $request->server->get('HTTP_CACHE_CONTROL'),
                'connection' => $request->server->get('HTTP_CONNECTION'),
                'proxy' => $request->server->get('HTTP_VIA'),
                'bearerType' => $request->server->get('HTTP_X_UP_BEARER_TYPE'),
                'httpProtocol' => $request->server->get('SERVER_PROTOCOL'),
                'accept' => $request->server->get('HTTP_ACCEPT'),
                'lang' => $request->server->get('HTTP_ACCEPT_LANGUAGE'),
                'charset' => $request->server->get('HTTP_ACCEPT_CHARSET'),
                'dnt' => $request->server->get('HTTP_DNT'),
            ));
    }
}
