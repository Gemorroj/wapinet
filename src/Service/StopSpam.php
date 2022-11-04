<?php

namespace App\Service;

use App\Entity\User;
use StopSpam\Query as StopSpamQuery;
use StopSpam\Request as StopSpamRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

class StopSpam
{
    private StopSpamRequest $request;
    private ?UserInterface $user;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->user = $tokenStorage->getToken() ? $tokenStorage->getToken()->getUser() : null;
        $this->request = new StopSpamRequest();
    }

    public function checkRequest(Request $request): void
    {
        if ($this->user instanceof User) {
            return; // доверяем зарегистрированным пользователям. (защита от ложных срабатываний на IP)
        }

        $query = new StopSpamQuery();
        $query->addIp($request->getClientIp());

        try {
            $response = $this->request->send($query);
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
