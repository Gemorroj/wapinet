<?php

namespace App\Service;

class MassMedia
{
    public function __construct(private Curl $curl)
    {
    }

    public function getRt(): array
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
                    if (\str_starts_with($type, 'image/')) {
                        $photo = (string) $enclosure->attributes()->url;
                    } elseif (\str_starts_with($type, 'video/')) {
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

    public function getInotv(): array
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
                    if (\str_starts_with($type, 'image/')) {
                        $photo = (string) $enclosure->attributes()->url;
                    } elseif (\str_starts_with($type, 'video/')) {
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
