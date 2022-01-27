<?php

namespace App\Controller;

use App\Service\Curl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vk_api')]
class VkApiController extends AbstractController
{
    #[Route(path: '/users', name: 'vk_api_users_get', options: ['expose' => true], defaults: ['_format' => 'json'], methods: ['POST'])]
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

        return new JsonResponse($response->getContent(), json: true);
    }
}
