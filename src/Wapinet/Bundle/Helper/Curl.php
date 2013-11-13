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
    protected $headers = array();
    protected $postData = array();


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
        if ($this->headers) {
            $this->setOpt(CURLOPT_HTTPHEADER, $this->headers);
        }
        if ($this->postData) {
            $this->sendPostData();
        }

        return curl_exec($this->curl);
    }

    /**
     * @return Curl
     */
    protected function sendPostData()
    {
        $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->setOpt(CURLOPT_POST, true);
        $post = '';
        foreach ($this->postData as $key => $value) {
            $post .= rawurlencode($key) . '=' . rawurlencode($value) . '&';
        }
        $post = rtrim($post, '&');
        $this->setOpt(CURLOPT_POSTFIELDS, $post);

        return $this;
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
     * @param string $key
     * @param string $value
     * @return Curl
     */
    public function addHeader($key, $value)
    {
        $this->headers[] = $key . ': ' . $value;
        return $this;
    }

    /**
     * @return Curl
     */
    public function addBrowserHeaders()
    {
        $this->headers = array_merge($this->headers, $this->browserHeaders);
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Curl
     */
    public function addPostData($key, $value)
    {
        $this->postData[$key] = $value;
        return $this;
    }
}
