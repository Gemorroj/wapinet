<?php
namespace WapinetBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * MassMedia хэлпер
 */
class MassMedia
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
     * @return array
     */
    public function getRt()
    {
        $curl = $this->container->get('curl');
        $curl->init('https://russian.rt.com/rss');
        $curl->addCompression();

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = \simplexml_load_string($response->getContent());

        $news = [];
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
                'description' => $this->stripDescription((string)$v->description),
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
    public function getInotv()
    {
        $curl = $this->container->get('curl');
        $curl->init('https://russian.rt.com/inotv/s/rss/inotv_main.rss');
        $curl->addCompression();

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = \simplexml_load_string($response->getContent());

        $news = [];
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
                'description' => $this->stripDescription((string)$v->description),
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
    private function stripDescription($str)
    {
        return \strip_tags($str);
    }
}
