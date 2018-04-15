<?php
namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * BotChecker хэлпер
 */
class BotChecker
{
    /**
     * @param Request $request
     * @throws AccessDeniedException
     */
    public function checkRequest(Request $request) : void
    {
        if ('' !== $request->get('bot-checker')) {
            throw new AccessDeniedException('Кажется, вы - спам-бот.');
        }
    }
}
