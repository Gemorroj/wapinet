<?php

namespace App\Service;

use App\Entity\User;
use StopSpam\Query as StopSpamQuery;
use StopSpam\Request as StopSpamRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class StopSpam
{
    private StopSpamRequest $httpClient;

    public function __construct(private Security $security, HttpClientInterface $httpClient)
    {
        $this->httpClient = new StopSpamRequest($httpClient);
    }

    public function checkRequest(Request $request): void
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return; // Доверяем зарегистрированным пользователям. (защита от ложных срабатываний на IP)
        }

        $query = new StopSpamQuery();
        $query->addIp($request->getClientIp());

        try {
            $response = $this->httpClient->send($query);
        } catch (\Exception $e) {
            // игнорируем проблемы с сетью или неработоспособность апи
            return;
        }

        $item = $response->getFlowingIp();
        if (null === $item) {
            return;
        }

        if ($item->isAppears()) {
            throw new AccessDeniedException('Ваш IP адрес находится в спам листе. Извините, вы не можете оставлять сообщения.');
        }
    }
}
