<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Rates
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function getName(string $country): ?string
    {
        return match ($country) {
            'ru' => 'Центральный банк Российской Федерации',
            'by' => 'Национальный банк Республики Беларусь',
            'ua' => 'Национальный банк Украины',
            'kz' => 'Национальный банк Республики Казахстан',
            default => null,
        };
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
        $response = $this->httpClient->request('GET', 'http://www.cbr.ru/scripts/XML_daily.asp');

        try {
            $data = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        $obj = \simplexml_load_string($data);
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
        $response = $this->httpClient->request('GET', 'https://www.nbrb.by/Services/XmlExRates.aspx');

        try {
            $data = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        $obj = \simplexml_load_string($data);
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
        $response = $this->httpClient->request('GET', 'https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json');

        try {
            $data = $response->toArray();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        $rates = [];
        foreach ($data as $v) {
            $rates[] = [
                'name' => $v['txt'],
                'code' => $v['cc'],
                'rate' => $v['rate'],
            ];
        }

        return [
            'date' => new \DateTime($data[0]['exchangedate'], new \DateTimeZone('Europe/Kiev')),
            'rates' => $rates,
        ];
    }

    protected function getKz(): array
    {
        $response = $this->httpClient->request('GET', 'https://www.nationalbank.kz/rss/rates_all.xml');

        try {
            $data = $response->getContent();
        } catch (HttpExceptionInterface $e) {
            throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
        }

        $obj = \simplexml_load_string($data);
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
