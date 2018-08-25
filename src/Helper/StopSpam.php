<?php
namespace App\Helper;

use StopSpam\Query as StopSpamQuery;
use StopSpam\Request as StopSpamRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class StopSpam
{
    private $request;

    public function __construct()
    {
        $this->request = new StopSpamRequest();
    }

	/**
	 * @param Request $request
	 */
    public function checkRequest(Request $request) : void
    {
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
