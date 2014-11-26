<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\HttpFoundation\Request;

/**
 * BotChecker хэлпер
 */
class BotChecker
{
    /**
     * @param Request $request
     * @throws \Exception
     */
    public function checkRequest(Request $request)
    {
        if ('' !== $request->get('bot-checker')) {
            throw new \Exception('Кажется, вы - спам-бот.');
        }
    }
}
