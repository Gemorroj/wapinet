<?php

namespace App\Helper;

/**
 * Horoscope хэлпер
 */
class Horoscope
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
     * @param string $zodiac
     *
     * @return string|null
     */
    public function getName(string $zodiac): ?string
    {
        switch ($zodiac) {
            case 'aries':
                return 'Овен';
                break;

            case 'taurus':
                return 'Телец';
                break;

            case 'gemini':
                return 'Близнецы';
                break;

            case 'cancer':
                return 'Рак';
                break;

            case 'leo':
                return 'Лев';
                break;

            case 'virgo':
                return 'Дева';
                break;

            case 'libra':
                return 'Весы';
                break;

            case 'scorpio':
                return 'Скорпион';
                break;

            case 'sagittarius':
                return 'Стрелец';
                break;

            case 'capricorn':
                return 'Козерог';
                break;

            case 'aquarius':
                return 'Водолей';
                break;

            case 'pisces':
                return 'Рыбы';
                break;
        }

        return null;
    }

    /**
     * @param string $zodiac
     *
     * @return array
     */
    public function getHoroscope(string $zodiac): array
    {
        $this->curl->init('http://hyrax.ru/rss_daily_common_'.$zodiac.'.xml');
        $this->curl->addCompression();
        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());

        return [
            'date' => new \DateTime((string) $obj->channel->item->pubDate),
            'horoscope' => (string) $obj->channel->item->description,
        ];
    }

    /**
     * @return array
     */
    public function getHoroscopeDay(): array
    {
        $this->curl->init('http://hyrax.ru/rss_daily_common.xml');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());

        return [
            'date' => new \DateTime((string) $obj->channel->item->pubDate),
            'horoscope' => (string) $obj->channel->item->description,
        ];
    }
}
