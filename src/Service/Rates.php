<?php

namespace App\Service;

/**
 * Rates хэлпер
 */
class Rates
{
    private Curl $curl;

    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    public function getName(string $country): ?string
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

    public function getRates(string $country): array
    {
        $method = 'get'.\ucfirst($country);
        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }
        throw new \RuntimeException('Указанная страна не поддерживается');
    }

    protected function getRu(): array
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
            'date' => new \DateTime((string) $obj->attributes()->Date, new \DateTimeZone('Europe/Moscow')),
            'rates' => $rates,
        ];
    }

    protected function getBy(): array
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
            'date' => new \DateTime((string) $obj->attributes()->Date, new \DateTimeZone('Europe/Minsk')),
            'rates' => $rates,
        ];
    }

    protected function getUa(): array
    {
        $this->curl->init('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json');
        $this->curl->addCompression();

        $response = $this->curl->exec();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $arr = \json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $rates = [];
        foreach ($arr as $v) {
            $rates[] = [
                'name' => $v['txt'],
                'code' => $v['cc'],
                'rate' => $v['rate'],
            ];
        }

        return [
            'date' => new \DateTime($arr[0]['exchangedate'], new \DateTimeZone('Europe/Kiev')),
            'rates' => $rates,
        ];
    }

    protected function getKz(): array
    {
        $this->curl->init('https://www.nationalbank.kz/rss/rates_all.xml');
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
            'date' => new \DateTime((string) $obj->channel->item[0]->pubDate, new \DateTimeZone('Asia/Almaty')),
            'rates' => $rates,
        ];
    }

    private function getKzRateName(string $code): ?string
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
