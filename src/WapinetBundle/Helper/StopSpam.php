<?php
namespace WapinetBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use StopSpam\Request as StopSpamRequest;
use StopSpam\Query as StopSpamQuery;

class StopSpam
{
    private $request;

    public function __construct()
    {
        $this->request = new StopSpamRequest();
    }

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function checkRequest(Request $request)
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

        if ($item->isAppears()) {
            throw new \Exception('Ваш IP адрес находится в спам листе. Извините, вы не можете оставлять сообщения.');
        }
    }
}