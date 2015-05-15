<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;


/**
 * Siza хэлпер
 */
class Siza
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var string
     */
    protected $contentDirectory;
    /**
     * @var string
     */
    protected $link;
    /**
     * @var \DOMDocument
     */
    protected $dom;
    /**
     * @var \DOMXpath
     */
    protected $xpath;

    /**
     * @var \DOMNodeList
     */
    private $foldersListChildNodes;
    /**
     * @var \DOMNodeList
     */
    private $foldersListDl;
    /**
     * @var \DOMNodeList
     */
    private $listingNagivation;
    /**
     * @var \DOMNodeList
     */
    private $contentList;
    /**
     * @var \DOMNodeList
     */
    private $contentNodes;
    /**
     * @var bool
     */
    private $ignoreNode = false;


    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $query
     * @return bool|int
     */
    public function getSearchId($query)
    {
        $this->link = 'http://load.siza.ru/search?place=everywhere&query=' . $query;
        $response = $this->getLink(true);

        if (false !== \strpos($response->getContent(), 'ничего не нашли')) {
            return false;
        }

        $location = $response->headers->get('Location');
        $query = \parse_url($location, PHP_URL_QUERY);
        list(, $searchId) = \explode('=', $query);

        return $searchId;
    }

    /**
     * Инициализируем работу с DOM моделью
     *
     * @param string $contentDirectory
     * @param string $query
     * @param int|null $page
     * @param int|null $scr
     * @param int|null $searchId
     */
    public function init($contentDirectory, $query, $page = null, $scr = null, $searchId = null)
    {
        $this->link = 'http://load.siza.ru/' . \ltrim($query, '/') . '?page=' . $page . '&scr=' . $scr . '&searchId=' . $searchId;
        $this->contentDirectory = $contentDirectory;
        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        @$this->dom->loadHTML($this->getLink()->getContent());

        $this->xpath = new \DOMXpath($this->dom);
        $this->foldersListChildNodes = $this->xpath->query('//div[@id="foldersList"]/div');
        $this->foldersListDl = $this->xpath->query('//div[@id="foldersList"]/dl/..');
        $this->listingNagivation = $this->xpath->query('//div[@id="listingNagivation"]');
        $this->contentList = $this->xpath->query('//div[@id="contentList"]');
        $this->contentNodes = $this->xpath->query('//div[@class="block"]');
    }

    /**
     * Получаем контент по ссылке
     *
     * @param bool $acceptRedirects
     * @return Response
     */
    public function getLink ($acceptRedirects = false)
    {
        $curl = $this->container->get('curl');
        $curl->init($this->link);
        $curl->addBrowserHeaders();
        $curl->addCompression();
        if ($acceptRedirects) {
            $curl->acceptRedirects();
        }

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        return $response;
    }


    /**
     * Получаем страницу со списком файлов
     *
     * @return string
     */
    public function getContentListSearch()
    {
        $out = $this->getContentList();

        $out = \str_replace(array('<dl><dt>', '<dl><dt style="border-bottom:none;">', '</dt>', '</dl>'), array('<li>', '<li>', '</p>', '</a></li>'), $out);
        $out = \str_replace('</d</ul>' , '</a></li></ul>', $out);


        return $out;
    }


    /**
     * Получаем страницу со списком файлов
     *
     * @return string
     */
    public function getContentList()
    {
        $out = '';

        if ($this->contentList->length) {
            /** @var \DOMElement $v */
            $v = $this->contentList->item(0);

            $aArr = $v->getElementsByTagName('a');
            /** @var \DOMElement $a */
            foreach ($aArr as $a) {
                $href = (string)$a->getAttribute('href');
                $a->setAttribute('href', '?q=' . $href);
            }

            $imgArr = $v->getElementsByTagName('img');
            $this->removeNodeList($imgArr, function (\DOMElement $img) {
                $src = (string)$img->getAttribute('src');

                if (0 === \strpos($src, '/pics/')) {
                    $img->parentNode->removeChild($img);
                } else {
                    $img->setAttribute('src', '?screen=yes&q=' . $src);
                }
            });


            $imgArr = $v->getElementsByTagName('img');
            $this->removeNodeList($imgArr, function (\DOMElement $img) {
                if ('a' === $img->parentNode->nodeName) {
                    $img->parentNode->parentNode->replaceChild($img, $img->parentNode);
                }
            });


            $brArr = $v->getElementsByTagName('br');
            $this->removeNodeList($brArr);


            $aArr = $v->getElementsByTagName('a');
            /** @var \DOMElement $a */
            foreach ($aArr as $a) {
                $img = $a->previousSibling;
                if ($img->nodeName === 'img') {
                    $tmpImg = $img->cloneNode();

                    // fix: DOMElement::__construct(): unterminated entity reference
                    $value = \htmlentities($a->firstChild->nodeValue, \ENT_NOQUOTES | \ENT_HTML5);

                    $tmpName = new \DOMElement('h2', $value);
                    $a->replaceChild($tmpImg, $a->firstChild);
                    $a->appendChild($tmpName);
                    $img->parentNode->removeChild($img);
                }
            }

            $out .= \str_replace(array('<div class="outer">', '<div class="outer" style="border:none;">', '</div>'), array('<li>', '<li>', '</li>'), $v->ownerDocument->saveXML($v));
        }

        if ('' === $out) {
            return '';
        }

        $out = \str_replace('&#13;', '', $out);
        $out = \rtrim($out, '</li>') . '</div>'; // fix
        $out = \str_replace('<br.../>', '...', $out);
        $out = \str_replace(array('</a>', '</li>'), array('<p>', '</p></a></li>'), $out);
        return '<ul data-role="listview" data-inset="true">' . \str_replace(array('<div id="contentList">', '</div>'), array('', ''), $out) . '</ul>';
    }


    /**
     * Получаем страницу со списком букв исполнителей
     *
     * @return string
     */
    public function getFoldersListDl()
    {
        $out = '';

        if ($this->foldersListDl->length) {
            /** @var \DOMElement $v */
            $v = $this->foldersListDl->item(0);

            $aArr = $v->getElementsByTagName('a');
            /** @var \DOMElement $a */
            foreach ($aArr as $a) {
                $href = (string)$a->getAttribute('href');
                $a->setAttribute('href', '?q=' . $href);
            }

            $out .= $v->ownerDocument->saveXML($v);
        }

        if ('' === $out) {
            return '';
        }

        $out = \str_replace(array('<div id="foldersList">', '</div>'), array('', ''), $out);
        return $out;
    }


    /**
     * Получаем страницу со списком директорий
     *
     * @return string
     */
    public function getFoldersList()
    {
        $out = '';
        $that = $this;

        /** @var \DOMElement $v */
        foreach ($this->foldersListChildNodes as $v) {
            $this->ignoreNode = false;

            $aArr = $v->getElementsByTagName('a');
            $this->removeNodeList($aArr, function (\DOMElement $a) use($that) {
                $href = (string)$a->getAttribute('href');
                $href = \str_replace('http://load.siza.ru', '', $href);

                if ($href === '/orders/') {
                    $a->parentNode->removeChild($a);
                } else if (0 === \strpos($href, 'http://')) {
                    $that->ignoreNode = true;
                } else {
                    $a->setAttribute('href', '?q=' . $href);
                }
            });

            $imgArr = $v->getElementsByTagName('img');
            $this->removeNodeList($imgArr, function (\DOMElement $img) use($that) {
                $src = (string)$img->getAttribute('src');
                if ($src !== '/pics/dir.gif') {
                    $that->ignoreNode = true;
                } else {
                    $img->parentNode->removeChild($img);
                }
            });

            $spanArr = $v->getElementsByTagName('span');
            /** @var \DOMElement $span */
            foreach ($spanArr as $span) {
                $span->setAttribute('class', 'ui-li-count');
                $span->previousSibling->previousSibling->appendChild(clone $span);
                $span->parentNode->removeChild($span);
            }


            if ($that->ignoreNode === false) {
                $out .= \str_replace(array('<div class="outer">', '<div class="outer" style="border:none;">', '</div>'), array('<li>', '<li>', '</li>'), $v->ownerDocument->saveXML($v));
            }
        }

        if ('' === $out) {
            return '';
        }
        return '<ul data-role="listview" data-inset="true">' . $out . '</ul>';
    }


    /**
     * Получаем страницу со пагинацией
     *
     * @param array $extParam
     * @return string
     */
    public function getListingNagivation(array $extParam = null)
    {
        $out = '';

        if ($this->listingNagivation->length) {
            /** @var \DOMElement $v */
            $v = $this->listingNagivation->item(0);

            $aArr = $v->getElementsByTagName('a');
            /** @var \DOMElement $a */
            foreach ($aArr as $a) {
                $href = (string)$a->getAttribute('href');
                $href = \str_replace('?', '&', $href);
                if ($extParam) {
                    foreach ($extParam as $extParamKey => $extParamValue) {
                        $href .= '&' . \rawurlencode($extParamKey) . '=' . \rawurlencode($extParamValue);
                    }
                }
                $a->setAttribute('href', '?q=' . $href);
                $a->setAttribute('data-role', 'button');
                $a->removeAttribute('class');
            }

            $out .= $v->ownerDocument->saveXML($v);
        }

        if (!$out) {
            return '';
        }

        $out = \str_replace(array("\t", "\r", "\n", '<br/>', '<span>|</span>', '<div id="listingNagivation">', '</div>'), array('', '', '', '', '', '', ''), $out);
        $out = \str_replace(array('<span>', '</span>'), array('<a href="#" class="ui-disabled" data-role="button">', '</a>'), $out);

        $out = \preg_replace('/<\/a>(\d+) /', '</a><a href="#" class="ui-disabled" data-role="button">$1</a> ', $out);
        $out = \preg_replace('/<\/a>\.\.\.<a/', '</a><a href="#" class="ui-disabled" data-role="button">...</a><a', $out);

        \preg_match('/(<a [^>]+>Далее<\/a>)/u', $out, $matches);
        $next = $matches[1];
        $out = \str_replace($next, '', $out);
        $out .= $next;

        return '<nav data-role="controlgroup" data-type="horizontal">' . $out . '</nav>';
    }


    /**
     * Получаем страницу с контентом
     *
     * @return string
     */
    public function getContent()
    {
        $out = '';
        $end = false;

        /** @var \DOMElement $v */
        foreach ($this->contentNodes as $v) {
            $tmp = $v->childNodes->item(4);
            if ($tmp && $tmp->nodeName === 'table') {
                continue; // пропускаем оценки
            }

            $scriptArr = $v->getElementsByTagName('script');
            $this->removeNodeList($scriptArr);

            /*$divArr = $v->getElementsByTagName('div');
            $this->removeNodeList($divArr, function (\DOMElement $div) {
                // реклама
                if ($div->hasAttribute('class') === false && $div->getElementsByTagName('img')->length === 0 && $div->parentNode->getAttribute('class') !== 'block') {
                    $div->parentNode->removeChild($div);
                }
            });*/

            $aArr = $v->getElementsByTagName('a');
            /** @var \DOMElement $a */
            foreach ($aArr as $a) {
                $href = (string)$a->getAttribute('href');

                // http://dwloadfiles.ru/api.php?a=104275&u=http%3A%2F%2Ff.siza.ru%2F65%2F6504%2F650435%2F19th_Club_Party_Night_Nok_128x128.jar%3Fr%3D75875388&n=19th Club Party Night&d=3&mt=1&s=1&ic=2501&im=2508&f=19th_Club_Party_Night_Nok_128x128.jar?r=75875388
                if (0 === \strpos($href, 'http://dwloadfiles.ru/api.php?')) {
                    // какой-то хак сайта
                    $parsedHref = \parse_url($href, \PHP_URL_QUERY);
                    \parse_str($parsedHref, $parsedHrefQuery);
                    $href = $parsedHrefQuery['u'];
                    // какой-то хак сайта
                }

                if (0 === \strpos($href, 'http://') && 0 !== \strpos($href, 'http://f.siza.ru')) {
                    $end = true;
                }

                if (0 === \strpos($href, 'http://f.siza.ru')) {

                    $prevNode = $a->previousSibling;
                    if ($prevNode->nodeType === \XML_TEXT_NODE && $prevNode->nodeValue === ' / ') {
                        $prevNode->parentNode->removeChild($prevNode);
                    }

                    $a->setAttribute('href', '?download=yes&q=' . \str_replace('http://f.siza.ru', '', $href));
                    $a->setAttribute('data-role', 'button');
                    $a->setAttribute('data-inline', 'true');
                    $a->setAttribute('data-icon', 'arrow-d');
                } else if (0 === \strpos($href, '/Image/') || 0 === \strpos($href, '/Screenshot/')) {

                    $prevNode = $a->previousSibling;
                    if ($prevNode->nodeType === \XML_TEXT_NODE && $prevNode->nodeValue === ' / ') {
                        $prevNode->parentNode->removeChild($prevNode);
                    }

                    $a->setAttribute('href', '?screen=yes&q=' . $href);
                    $a->setAttribute('data-role', 'button');
                    $a->setAttribute('data-inline', 'true');
                    $a->setAttribute('data-icon', 'arrow-d');
                } else {
                    $href = \str_replace('?scr=', '&scr=', $href);
                    $a->setAttribute('href', '?q=' . $href);
                }
            }

            $imgArr = $v->getElementsByTagName('img');
            /** @var \DOMElement $img */
            foreach ($imgArr as $img) {
                $src = (string)$img->getAttribute('src');
                $img->setAttribute('src', '?screen=yes&q=' . $src);
            }

            $objectArr = $v->getElementsByTagName('object');
            /** @var \DOMElement $object */
            foreach ($objectArr as $object) {
                $data = (string)$object->getAttribute('data');

                if (0 === \strpos($data, '/swf/dewplayer-rect.swf')) {
                    $object->setAttribute('data', $this->contentDirectory . '/' . $data);
                }
            }

            if ($end === true) {
                break;
            } else {
                $out .= $v->ownerDocument->saveXML($v);
            }
        }

        $out = \str_replace(array('<span/>', '<b/>'), '', $out);
        return $out;
    }


    /**
     * Получаем страницу с бредкрамбсами
     *
     * @return array
     */
    public function getContentNavigator()
    {
        $out = array(array('query' => '', 'name' => 'Загрузки'));

        /** @var \DOMElement $v */
        foreach ($this->contentNodes as $v) {
            $tmp = $v->childNodes->item(1);
            if ($tmp && $tmp->nodeName === 'a' && $tmp->nodeValue === 'На главную') {
                $aArr = $v->getElementsByTagName('a');
                $i = 0;
                /** @var \DOMElement $a */
                foreach ($aArr as $a) {
                    $i++;
                    if ($i < 3) {
                        continue;
                    }  else {
                        $href = (string)$a->getAttribute('href');
                        $out[] = array('query' => $href, 'name' => $a->nodeValue);
                    }
                }

                $out[] = array('query' => null, 'name' => \ltrim(\trim($v->lastChild->nodeValue), '» '));
                break;
            }
        }

        return $out;
    }

    /**
     * @see http://www.php.net/manual/ru/domnode.removechild.php#90292
     * @param \DOMNodeList $nodeList
     * @param \Closure|null $callback
     */
    private function removeNodeList(\DOMNodeList $nodeList, \Closure $callback = null)
    {
        $removeArr = array();
        foreach ($nodeList as $domElement) {
            $removeArr[] = $domElement;
        }
        foreach ($removeArr as $domElement) {
            if (null !== $callback) {
                $callback($domElement);
            } else {
                $domElement->parentNode->removeChild($domElement);
            }
        }
    }
}
