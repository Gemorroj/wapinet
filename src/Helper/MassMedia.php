<?php

namespace App\Helper;

/**
 * MassMedia хэлпер
 */
class MassMedia
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @param Curl $curl
     */
    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @return array
     */
    public function getRt()
    {
        $this->curl->init('https://russian.rt.com/rss');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());

        $news = [];
        foreach ($obj->channel->item as $v) {
            $photo = null;
            $video = null;

            if ($v->enclosure) {
                foreach ($v->enclosure as $enclosure) {
                    $type = (string) $enclosure->attributes()->type;
                    if ('image/' === \mb_substr($type, 0, 6)) {
                        $photo = (string) $enclosure->attributes()->url;
                    } elseif ('video/' === \mb_substr($type, 0, 6)) {
                        $video = (string) $enclosure->attributes()->url;
                    }
                }
            }

            $news[] = [
                'datetime' => new \DateTime((string) $v->pubDate.'C'),
                'title' => (string) $v->title,
                'description' => $this->stripDescription((string) $v->description),
                'author' => null,
                'category' => (string) $v->category,
                'link' => (string) $v->link,
                'photo' => $photo,
                'video' => $video,
            ];
        }

        return $news;
    }

    /**
     * @return array
     */
    public function getInotv()
    {
        $this->curl->init('https://russian.rt.com/inotv/s/rss/inotv_main.rss');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());

        $news = [];
        foreach ($obj->channel->item as $v) {
            $photo = null;
            $video = null;

            if ($v->enclosure) {
                foreach ($v->enclosure as $enclosure) {
                    $type = (string) $enclosure->attributes()->type;
                    if ('image/' === \mb_substr($type, 0, 6)) {
                        $photo = (string) $enclosure->attributes()->url;
                    } elseif ('video/' === \mb_substr($type, 0, 6)) {
                        $video = (string) $enclosure->attributes()->url;
                    }
                }
            }

            $news[] = [
                'datetime' => new \DateTime((string) $v->pubDate.'C'),
                'title' => (string) $v->title,
                'description' => $this->stripDescription((string) $v->description),
                'author' => (string) $v->author,
                'category' => null,
                'link' => (string) $v->link,
                'photo' => $photo,
                'video' => $video,
            ];
        }

        return $news;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    private function stripDescription(string $str): string
    {
        return \strip_tags($str);
    }
}
