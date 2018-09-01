<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class BrowserInfoController extends AbstractController
{
    /**
     * @param Request $request
     *
     * @return string|null
     */
    protected function getPhoneNumber(Request $request)
    {
        if ($request->server->has('HTTP_X_NETWORK_INFO')) {
            return \preg_replace('/(.*,)(11[d])(,.*)/i', '$2', $request->server->get('HTTP_X_NETWORK_INFO'));
        }
        if ($request->server->has('HTTP_X_UP_CALLING_LINE_ID')) {
            return $request->server->get('HTTP_X_UP_CALLING_LINE_ID');
        }
        if ($request->server->has('HTTP_X_UP_SUBNO')) {
            return \preg_replace('/(.*)(11[d])(.*)/i', '$2', $request->server->get('HTTP_X_UP_SUBNO'));
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

        return null;
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    protected function getProxy(Request $request)
    {
        if ($request->server->has('HTTP_X_FORWARDED_FOR')) {
            return $request->server->get('HTTP_X_FORWARDED_FOR');
        }
        if ($request->server->has('HTTP_CLIENT_IP')) {
            return $request->server->get('HTTP_CLIENT_IP');
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    protected function getProxyHost(Request $request)
    {
        $proxy = $this->getProxy($request);
        if (null !== $proxy) {
            $proxyHost = [];
            foreach (\explode(',', $proxy) as $v) {
                $proxyHost[] = \gethostbyaddr(\trim($v));
            }

            return \implode(',', $proxyHost);
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    protected function getEncoding(Request $request)
    {
        if ($request->server->has('HTTP_ACCEPT_ENCODING')) {
            return $request->server->get('HTTP_ACCEPT_ENCODING');
        }
        if ($request->server->has('HTTP_TE')) {
            return $request->server->get('HTTP_TE');
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $headers = clone $request->headers;
        $headers->remove('X-Php-Ob-Level');

        return $this->render('BrowserInfo/index.html.twig', [
            'user_agent' => $request->server->get('HTTP_USER_AGENT'),
            'phone_number' => $this->getPhoneNumber($request),
            'ip' => $request->server->get('REMOTE_ADDR'),
            'ip_host' => \gethostbyaddr($request->server->get('REMOTE_ADDR')),
            'local' => $this->getProxy($request),
            'local_host' => $this->getProxyHost($request),
            'remote_port' => $request->server->get('REMOTE_PORT'),
            'remote_user' => $request->server->get('REMOTE_USER'),
            'referer' => $request->server->get('HTTP_REFERER'),
            'encoding' => $this->getEncoding($request),
            'cache' => $request->server->get('HTTP_CACHE_CONTROL'),
            'connection' => $request->server->get('HTTP_CONNECTION'),
            'proxy' => $request->server->get('HTTP_VIA'),
            'http_protocol' => $request->server->get('SERVER_PROTOCOL'),
            'accept' => $request->server->get('HTTP_ACCEPT'),
            'lang' => $request->server->get('HTTP_ACCEPT_LANGUAGE'),
            'charset' => $request->server->get('HTTP_ACCEPT_CHARSET'),
            'dnt' => $request->server->get('HTTP_DNT'),
            'all' => $headers,
        ]);
    }
}
