<?php
namespace App\Helper;

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
        $method = 'get' . \ucfirst($country);
        if (\method_exists($this, $method)) {
            return $this->{$method}();
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

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->Valute as $v) {
            $rates[] = [
                'name' => (string)$v->Name,
                'code' => (string)$v->CharCode,
                'rate' => (string)$v->Value,
            ];
        }

        return [
            'date' => new \DateTime((string)$obj->attributes()->Date),
            'rates' => $rates,
        ];
    }

    /**
     * @return array
     */
    protected function getBy()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://nbrb.by/Services/XmlExRates.aspx');
        $curl->addCompression();

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->Currency as $v) {
            $rates[] = [
                'name' => (string)$v->Name,
                'code' => (string)$v->CharCode,
                'rate' => (string)$v->Rate,
            ];
        }

        return [
            'date' => new \DateTime((string)$obj->attributes()->Date),
            'rates' => $rates,
        ];
    }

    /**
     * @return array
     */
    protected function getUa()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://bank-ua.com/export/currrate.xml');
        $curl->addCompression();

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->item as $v) {
            $rates[] = [
                'name' => (string)$v->name,
                'code' => (string)$v->char3,
                'rate' => (string)$v->change,
            ];
        }

        return [
            'date' => new \DateTime((string)$obj->item[0]->date),
            'rates' => $rates,
        ];
    }

    /**
     * @return array
     */
    protected function getKz()
    {
        $curl = $this->container->get('curl');
        $curl->init('http://www.nationalbank.kz/rss/rates_all.xml');
        $curl->addCompression();

        $response = $curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->channel->item as $v) {
            $rates[] = [
                'name' => $this->getKzRateName((string)$v->title),
                'code' => (string)$v->title,
                'rate' => (string)$v->description,
            ];
        }

        return [
            'date' => new \DateTime((string)$obj->channel->item[0]->pubDate),
            'rates' => $rates,
        ];
    }


    /**
     * @param string $code
     * @return string|null
     */
    private function getKzRateName($code)
    {
        static $ruRates = null;
        $ruRates = $ruRates ?: $this->getRu()['rates'];

        foreach ($ruRates as $ruRate) {
            if ($ruRate['code'] === $code) {
                return $ruRate['name'];
            }
        }

        static $byRates = null;
        $byRates = $byRates ?: $this->getBy()['rates'];

        foreach ($byRates as $byRate) {
            if ($byRate['code'] === $code) {
                return $byRate['name'];
            }
        }

        return null;
    }
}
