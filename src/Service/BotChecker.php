<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class BotChecker
{
    /**
     * @throws AccessDeniedException
     */
    public function checkRequest(Request $request): void
    {
        if ('' !== $request->get('b-check')) {
            throw new AccessDeniedException('Кажется, вы - спам-бот.');
        }
    }
}
