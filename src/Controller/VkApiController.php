<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/vk_api')]
class VkApiController extends AbstractController
{
    #[Route(path: '/users', name: 'vk_api_users_get', options: ['expose' => true], defaults: ['_format' => 'json'], methods: ['POST'])]
    public function getUsersAction(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $params = $request->request->all();
        $params['access_token'] = $this->getParameter('wapinet_vk_access_token');

        $response = $httpClient->request('GET', 'https://api.vk.com/method/users.get', [
            'query' => $params,
        ]);
        try {
            $data = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        return new JsonResponse($data, json: true);
    }
}
