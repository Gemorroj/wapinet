<?php

namespace App\Service;

use App\Pagerfanta\FixedPaginate;
use Pagerfanta\Pagerfanta;
use const PREG_SET_ORDER;
use RuntimeException;

/**
 * Bash хэлпер
 */
class Bash
{
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var Paginate
     */
    private $paginate;

    public function __construct(Curl $curl, Paginate $paginate)
    {
        $this->curl = $curl;
        $this->paginate = $paginate;
    }

    public function getPage(?int $page = null): Pagerfanta
    {
        $this->curl->init('https://bash.im/index/'.$page);
        $this->curl->addBrowserHeaders();
        $this->curl->addCompression();
        $response = $this->curl->exec();
        $this->curl->close();

        if (!$response->isSuccessful()) {
            throw new RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $content = \str_replace(["\n", "\r", "\t", '<br>'], ['', '', '', "\r\n"], $response->getContent());

        // количество цитат на странице
        $maxPerPage = 25;

        // получаем общее количество страниц
        \preg_match('/data-page numeric="integer" min="1" max="(\d+)" data-path="index"/', $content, $matchPage);
        $allPages = $matchPage[1];

        // текущая страница
        $currentPage = $page ?? $allPages;

        // вырезаем цитаты
        \preg_match_all('/(?:<div class="quote__body">+)(.*?)(?:<\/div>+)/is', $content, $matchItems, PREG_SET_ORDER);
        unset($matchItems[0]); // <span>Утверждено <b>73394</b> цитаты, </span>

        // заносим цитаты в массив
        $items = [];
        foreach ($matchItems as $v) {
            $items[] = \strip_tags($v[1]);
        }

        // создаем фиксированный пагинатор
        $paginate = new FixedPaginate($allPages * $maxPerPage, $items);

        return $this->paginate->paginate($paginate, $currentPage, $maxPerPage);
    }
}
