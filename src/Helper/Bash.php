<?php

namespace App\Helper;

use App\Pagerfanta\FixedPaginate;
use Pagerfanta\Pagerfanta;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strip_tags;
use const PREG_SET_ORDER;

/**
 * Bash хэлпер
 */
class Bash
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param int|null $page
     *
     * @return Pagerfanta
     */
    public function getPage($page = null)
    {
        $curl = $this->container->get('curl');
        $curl->init('https://bash.im/index/'.$page);
        $curl->addBrowserHeaders();
        $curl->addCompression();
        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $content = str_replace(["\n", "\r", "\t", '<br>'], ['', '', '', "\r\n"], $response->getContent());

        // количество цитат на странице
        $maxPerPage = 25;

        // получаем общее количество страниц
        preg_match('/data-page numeric="integer" min="1" max="(\d+)" data-path="index"/', $content, $matchPage);
        $allPages = $matchPage[1];

        // текущая страница
        $currentPage = null === $page ? $allPages : $page;

        // вырезаем цитаты
        preg_match_all('/(?:<div class="quote__body">+)(.*?)(?:<\/div>+)/is', $content, $matchItems, PREG_SET_ORDER);
        unset($matchItems[0]); // <span>Утверждено <b>73394</b> цитаты, </span>

        // заносим цитаты в массив
        $items = [];
        foreach ($matchItems as $v) {
            $items[] = strip_tags($v[1]);
        }

        // создаем фиксированный пагинатор
        $paginate = new FixedPaginate($allPages * $maxPerPage, $items);

        return $this->container->get('paginate')->paginate($paginate, $currentPage, $maxPerPage);
    }
}
