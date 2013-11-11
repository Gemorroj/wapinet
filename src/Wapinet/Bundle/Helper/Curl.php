<?php
namespace Wapinet\Bundle\Helper;

/**
 * CURL хэлпер
 */
class Curl
{
    /**
     * @var resource
     */
    private $curl;

    protected $browserHeaders = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Connection: Close',
        'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
        'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
    );


    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
    }

    /**
     * Задаем опцию
     *
     * @param int $key
     * @param mixed $value
     * @return Curl
     */
    public function setOpt($key, $value)
    {
        curl_setopt($this->curl, $key, $value);
        return $this;
    }

    /**
     * Получаем результат
     *
     * @return mixed
     */
    public function exec()
    {
        return curl_exec($this->curl);
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }


    /**
     * Получение ресурса CURL
     *
     * @return resource
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * @return Curl
     */
    public function addCompression()
    {
        $this->setOpt(CURLOPT_ENCODING, '');
        return $this;
    }

    /**
     * @return Curl
     */
    public function addBrowserHeaders()
    {
        $this->setOpt(CURLOPT_HTTPHEADER, $this->browserHeaders);
        return $this;
    }
}
