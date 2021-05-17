<?php

namespace App\Controller;

use App\Service\Curl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/vk_api")
 */
class VkApiController extends AbstractController
{
    /**
     * @Route("/get/{method}", name="vk_api_users_get", defaults={"_format": "json"}, methods={"POST"}, options={"expose": true})
     */
    public function getUsersAction(Request $request, Curl $curl): JsonResponse
    {
        $params = $request->request->all();
        $params['access_token'] = $this->getParameter('wapinet_vk_access_token');

        $query = \http_build_query($params);

        $curl->init('https://api.vk.com/method/users.get?'.$query);
        $curl->addCompression();

        $response = $curl->exec();
        if (!$response->isSuccessful()) {
            throw new HttpException($response->getStatusCode());
        }

        $json = \json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        return new JsonResponse($json);
    }
}
