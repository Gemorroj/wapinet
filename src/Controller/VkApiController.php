<?php

namespace App\Controller;

use App\Service\Curl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VkApiController extends AbstractController
{
    public function getAction(Request $request, string $method, Curl $curl): JsonResponse
    {
        $params = $request->request->all();
        $params['access_token'] = $this->getParameter('wapinet_vk_access_token');

        $query = \http_build_query($params);

        $curl->init('https://api.vk.com/method/'.$method.'?'.$query);
        $curl->addCompression();

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode());
        }

        $json = \json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        return new JsonResponse($json);
    }
}
