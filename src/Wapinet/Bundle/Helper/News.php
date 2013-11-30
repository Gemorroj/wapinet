<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * News хэлпер
 */
class News
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getNewsRt()
    {
        $curl = $this->container->get('curl');
        $curl->addCompression();
        $curl->setOpt(CURLOPT_URL, 'http://russian.rt.com/rss/');
        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());

        $news = array();
        foreach ($obj->channel->item as $v) {
            $photo = null;
            $video = null;

            if ($v->enclosure) {
                foreach ($v->enclosure as $enclosure) {
                    $type = (string)$enclosure->attributes()->type;
                    if ('image/' === substr($type, 0, 6)) {
                        $photo = (string)$enclosure->attributes()->url;
                    } elseif ('video/' === substr($type, 0, 6)) {
                        $video = (string)$enclosure->attributes()->url;
                    }
                }
            }

            $news[] = array(
                'datetime' => new \DateTime((string)$v->pubDate . 'C'),
                'title' => (string)$v->title,
                'description' => $this->stripLink((string)$v->description),
                'author' => null,
                'category' => (string)$v->category,
                'link' => (string)$v->link,
                'photo' => $photo,
                'video' => $video,
            );
        }

        return $news;
    }


    /**
     * @return array
     */
    public function getNewsInotv()
    {
        $curl = $this->container->get('curl');
        $curl->addCompression();
        $curl->setOpt(CURLOPT_URL, 'http://inotv.rt.com/s/rss/main.rss');
        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());

        $news = array();
        foreach ($obj->channel->item as $v) {
            $photo = null;
            $video = null;

            if ($v->enclosure) {
                foreach ($v->enclosure as $enclosure) {
                    $type = (string)$enclosure->attributes()->type;
                    if ('image/' === substr($type, 0, 6)) {
                        $photo = (string)$enclosure->attributes()->url;
                    } elseif ('video/' === substr($type, 0, 6)) {
                        $video = (string)$enclosure->attributes()->url;
                    }
                }
            }

            $news[] = array(
                'datetime' => new \DateTime((string)$v->pubDate . 'C'),
                'title' => (string)$v->title,
                'description' => $this->stripLink((string)$v->description),
                'author' => (string)$v->author,
                'category' => null,
                'link' => (string)$v->link,
                'photo' => $photo,
                'video' => $video,
            );
        }

        return $news;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function stripLink($str)
    {
        return preg_replace('/<a\s+.+<\s*\/\s*a>/i', '', $str);
    }
}
