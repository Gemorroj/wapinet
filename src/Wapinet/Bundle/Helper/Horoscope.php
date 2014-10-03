<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Horoscope хэлпер
 */
class Horoscope
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
     * @param string $zodiac
     * @return null|string
     */
    public function getName($zodiac)
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
     * @return array
     */
    public function getHoroscope($zodiac)
    {
        $curl = $this->container->get('curl');
        $curl->init('http://hyrax.ru/rss_daily_common_' . $zodiac . '.xml');
        $curl->addCompression();
        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = simplexml_load_string($response->getContent());

        return array(
            'date' => new \DateTime((string)$obj->channel->item->pubDate),
            'horoscope' => (string)$obj->channel->item->description,
        );
    }


    /**
     * @return array
     */
    public function getHoroscopeDay()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://hyrax.ru/rss_daily_common.xml');
        $curl->addCompression();

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = simplexml_load_string($response->getContent());

        return array(
            'date' => new \DateTime((string)$obj->channel->item->pubDate),
            'horoscope' => (string)$obj->channel->item->description,
        );
    }
}
