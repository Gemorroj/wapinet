<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * BotChecker хэлпер
 */
class BotChecker
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRequest(Request $request): void
    {
        if ('' !== $request->get('bot-checker')) {
            throw new AccessDeniedException('Кажется, вы - спам-бот.');
        }
    }
}