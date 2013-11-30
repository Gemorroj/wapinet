<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Weather хэлпер
 */
class Weather
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
    public function getCountries()
    {
        $file = $this->container->get('kernel')->getCacheWeatherDir() . '/countries.xml';

        if (false === is_file($file)) {
            $f = fopen($file, 'w');

            $curl = $this->container->get('curl');
            $curl->addCompression();
            $curl->setOpt(CURLOPT_URL, 'http://a956e985.services.gismeteo.ru/inform-service/588c42b53455e4d92511627521555fe4/countries/?lang=ru');
            $curl->setOpt(CURLOPT_FILE, $f);
            $curl->setOpt(CURLOPT_HEADER, false);
            $curl->exec();
            $curl->close();
            fclose($f);
        }

        $obj = simplexml_load_file($file);

        $countries = array();
        foreach ($obj->item as $v) {
            $countries[(string)$v->attributes()->id] = (string)$v->attributes()->n;
        }

        return $countries;
    }

    /**
     * @param int $country
     * @return array
     */
    public function getCities($country)
    {
        $file = $this->container->get('kernel')->getCacheWeatherDir() . '/' . $country . '.xml';

        if (false === is_file($file)) {
            $f = fopen($file, 'w');

            $curl = $this->container->get('curl');
            $curl->addCompression();
            $curl->setOpt(CURLOPT_URL, 'http://a956e985.services.gismeteo.ru/inform-service/588c42b53455e4d92511627521555fe4/cities/?country=' . $country . '&lang=ru');
            $curl->setOpt(CURLOPT_FILE, $f);
            $curl->setOpt(CURLOPT_HEADER, false);
            $curl->exec();
            $curl->close();
            fclose($f);
        }

        $obj = simplexml_load_file($file);

        $cities = array();
        foreach ($obj->item as $v) {
            $cities[(string)$v->attributes()->id] = (string)$v->attributes()->n;
        }

        return $cities;
    }


    /**
     * @param int $city
     * @return array
     */
    public function getWeather($city)
    {
        $curl = $this->container->get('curl');
        $curl->addCompression();
        $curl->setOpt(CURLOPT_URL, 'http://a956e985.services.gismeteo.ru/inform-service/588c42b53455e4d92511627521555fe4/forecast/?cities=' . $city);
        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());

        $weather =  array(
            'now' => array(
                'temperature' => $this->addPlusMinus((string)$obj->location->fact->values->attributes()->t),
                'pressure' => (string)$obj->location->fact->values->attributes()->p,
                'humidity' => (string)$obj->location->fact->values->attributes()->hum,
                'wind' => $this->getWindDirection((string)$obj->location->fact->values->attributes()->wd) . '. ' . (string)$obj->location->fact->values->attributes()->cl . ', ' . (string)$obj->location->fact->values->attributes()->ws,
                'description' => (string)$obj->location->fact->values->attributes()->descr,
            ),
            'forecast' => array(),
        );

        foreach ($obj->location->forecast as $v) {
            $weather['forecast'][] =  array(
                'datetime' => new \DateTime((string)$v->attributes()->valid),
                'temperature' => $this->addPlusMinus((string)$v->values->attributes()->t),
                'pressure' => (string)$v->values->attributes()->p,
                'humidity' => (string)$v->values->attributes()->hum,
                'wind' => $this->getWindDirection((string)$v->values->attributes()->wd) . '. ' . (string)$v->values->attributes()->cl . ', ' . (string)$v->values->attributes()->ws,
                'description' => (string)$v->values->attributes()->descr,
            );
        }

        return $weather;
    }


    /**
     * @param int $val
     *
     * @return string
     */
    protected function addPlusMinus ($val)
    {
        return ($val > 0 ? '+' . $val : $val);
    }

    /**
     * @param int $w
     *
     * @return string
     */
    protected function getWindDirection ($w)
    {
        $direction = array('штиль', 'северный', 'северо-восточный', 'восточный', 'юго-восточный', 'южный', 'юго-западный', 'западный', 'северо-западный');
        return $direction[$w];
    }
}
