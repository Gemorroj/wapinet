<?php

namespace App\Helper;

/**
 * Rates хэлпер
 */
class Rates
{
    /**
     * @var Curl
     */
    protected $curl;

    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    /**
     * @param string $country
     *
     * @return string|null
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
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    public function getRates($country)
    {
        $method = 'get'.\ucfirst($country);
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
        $this->curl->init('http://www.cbr.ru/scripts/XML_daily.asp');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->Valute as $v) {
            $rates[] = [
                'name' => (string) $v->Name,
                'code' => (string) $v->CharCode,
                'rate' => (string) $v->Value,
            ];
        }

        return [
            'date' => new \DateTime((string) $obj->attributes()->Date),
            'rates' => $rates,
        ];
    }

    /**
     * @return array
     */
    protected function getBy()
    {
        $this->curl->init('http://nbrb.by/Services/XmlExRates.aspx');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->Currency as $v) {
            $rates[] = [
                'name' => (string) $v->Name,
                'code' => (string) $v->CharCode,
                'rate' => (string) $v->Rate,
            ];
        }

        return [
            'date' => new \DateTime((string) $obj->attributes()->Date),
            'rates' => $rates,
        ];
    }

    /**
     * @return array
     */
    protected function getUa()
    {
        $this->curl->init('http://bank-ua.com/export/currrate.xml');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->item as $v) {
            $rates[] = [
                'name' => (string) $v->name,
                'code' => (string) $v->char3,
                'rate' => (string) $v->change,
            ];
        }

        return [
            'date' => new \DateTime((string) $obj->item[0]->date),
            'rates' => $rates,
        ];
    }

    /**
     * @return array
     */
    protected function getKz()
    {
        $this->curl->init('http://www.nationalbank.kz/rss/rates_all.xml');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $obj = \simplexml_load_string($response->getContent());
        $rates = [];
        foreach ($obj->channel->item as $v) {
            $rates[] = [
                'name' => $this->getKzRateName((string) $v->title),
                'code' => (string) $v->title,
                'rate' => (string) $v->description,
            ];
        }

        return [
            'date' => new \DateTime((string) $obj->channel->item[0]->pubDate),
            'rates' => $rates,
        ];
    }

    /**
     * @param string $code
     *
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
