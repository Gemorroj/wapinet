<?php
namespace WapinetBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Pagerfanta\Pagerfanta;
use WapinetBundle\Pagerfanta\FixedPaginate;

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
     * @return Pagerfanta
     */
    public function getPage($page = null)
    {
        $curl = $this->container->get('curl');
        $curl->init('http://bash.im/index/' . $page);
        $curl->addBrowserHeaders();
        $curl->addCompression();
        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $content = \mb_convert_encoding($response->getContent(), 'UTF-8', 'Windows-1251');
        $content = \str_replace(array("\n", "\r", "\t", '<br>'), array('', '', '', "\r\n"), $content);

        // количество цитат на странице
        $maxPerPage = 50;

        // получаем общее количество страниц
        \preg_match('/alert\("Нужно указать номер страницы от 1 до (\d+)"\);/u', $content, $matchPage);
        $allPages = $matchPage[1];

        // текущая страница
        $currentPage = null === $page ? $allPages : $page;

        // вырезаем цитаты
        \preg_match_all('/(?:<div class="text">+)(.*?)(?:<\/div>+)/is', $content, $matchItems, PREG_SET_ORDER);

        // заносим цитаты в массив
        $items = array();
        foreach($matchItems as $v) {
            $items[] = \strip_tags($v[1]);
        }

        // создаем фиксированный пагинатор
        $paginate = new FixedPaginate($allPages * $maxPerPage, $items);

        return $this->container->get('paginate')->paginate($paginate, $currentPage, $maxPerPage);
    }
}
