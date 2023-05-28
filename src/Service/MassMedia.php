<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MassMedia
{
    public function __construct(private HttpClientInterface $httpClient)
    {
        $this->httpClient = HttpClient::createForBaseUri('https://russian.rt.com');
    }

    public function getRt(): array
    {
        $response = $this->httpClient->request('GET', '/rss');

        try {
            $data = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        $obj = \simplexml_load_string($data);

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
        $response = $this->httpClient->request('GET', '/inotv/s/rss/inotv_main.rss');

        try {
            $data = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        $obj = \simplexml_load_string($data);

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
