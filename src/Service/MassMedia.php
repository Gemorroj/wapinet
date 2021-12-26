<?php

namespace App\Service;

/**
 * MassMedia хэлпер
 */
class MassMedia
{
    private Curl $curl;

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
                    if (0 === \mb_strpos($type, 'image/')) {
                        $photo = (string) $enclosure->attributes()->url;
                    } elseif (0 === \mb_strpos($type, 'video/')) {
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
                    if (0 === \mb_strpos($type, 'image/')) {
                        $photo = (string) $enclosure->attributes()->url;
                    } elseif (0 === \mb_strpos($type, 'video/')) {
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

    private function stripDescription(string $str): string
    {
        return \strip_tags($str);
    }
}
