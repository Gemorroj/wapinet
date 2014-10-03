<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Rates хэлпер
 */
class Rates
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
     * @param string $country
     * @return null|string
     */
    public function getName($country)
    {
        switch ($country) {
            case 'ru':
                return 'Центральный банк Российской Федерации';
                break;

            case 'by':
                return 'Национальный банк Республики Беларусь';
                break;

            case 'ua':
                return 'Национальный банк Украины';
                break;

            case 'kz':
                return 'Национальный банк Республики Казахстан';
                break;
        }

        return null;
    }


    /**
     * @param string $country
     * @return array
     * @throws \RuntimeException
     */
    public function getRates($country)
    {
        $method = 'get' . ucfirst($country);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        throw new \RuntimeException('Указанная страна не поддерживается');
    }


    /**
     * @return array
     */
    protected function getRu()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://www.cbr.ru/scripts/XML_daily.asp');
        $curl->addCompression();

        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());
        $rates = array();
        foreach ($obj->Valute as $v) {
            $rates[] = array(
                'name' => (string)$v->Name,
                'code' => (string)$v->CharCode,
                'rate' => (string)$v->Value,
            );
        }

        return array(
            'date' => new \DateTime((string)$obj->attributes()->Date),
            'rates' => $rates,
        );
    }

    /**
     * @return array
     */
    protected function getBy()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://nbrb.by/Services/XmlExRates.aspx');
        $curl->addCompression();

        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());
        $rates = array();
        foreach ($obj->Currency as $v) {
            $rates[] = array(
                'name' => (string)$v->Name,
                'code' => (string)$v->CharCode,
                'rate' => (string)$v->Rate,
            );
        }

        return array(
            'date' => new \DateTime((string)$obj->attributes()->Date),
            'rates' => $rates,
        );
    }

    /**
     * @return array
     */
    protected function getUa()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://bank-ua.com/export/currrate.xml');
        $curl->addCompression();

        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());
        $rates = array();
        foreach ($obj->item as $v) {
            $rates[] = array(
                'name' => (string)$v->name,
                'code' => (string)$v->char3,
                'rate' => (string)$v->change,
            );
        }

        return array(
            'date' => new \DateTime((string)$obj->item[0]->date),
            'rates' => $rates,
        );
    }

    /**
     * @return array
     */
    protected function getKz()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://www.nationalbank.kz/rss/rates_all.xml');
        $curl->addCompression();

        $result = $curl->exec();

        $obj = simplexml_load_string($result->getContent());
        $rates = array();
        foreach ($obj->channel->item as $v) {
            $rates[] = array(
                'name' => null,
                'code' => (string)$v->title,
                'rate' => (string)$v->description,
            );
        }

        return array(
            'date' => new \DateTime((string)$obj->channel->item[0]->pubDate),
            'rates' => $rates,
        );
    }
}
