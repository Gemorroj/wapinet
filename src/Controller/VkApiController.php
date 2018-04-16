<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VkApiController extends Controller
{
    public function getAction(Request $request, string $method)
    {
        $params = $request->request->all();
        $params['access_token'] = $this->getParameter('wapinet_vk_access_token');

        $query = \http_build_query($params);

        $curl = $this->get('curl')->init('https://api.vk.com/method/' . $method . '?' . $query);
        $curl->addCompression();

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode());
        }

        $json = \json_decode($response->getContent(), true);
        return new JsonResponse($json);
    }
}
