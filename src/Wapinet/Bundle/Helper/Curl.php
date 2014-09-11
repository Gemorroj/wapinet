<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\HttpFoundation\Response;
use Wapinet\Bundle\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CURL хэлпер
 */
class Curl
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var resource
     */
    private $curl;

    /**
     * @var array
     */
    protected $browserHeaders = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Connection: Close',
        'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
        'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:25.0) Gecko/20100101 Firefox/25.0',
    );
    protected $headers = array();
    protected $postData = array();


    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->curl = curl_init();
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_HEADER, true);
    }

    /**
     * Деструктор
     */
    /*public function __destruct()
    {
        $this->close();
    }*/

    /**
     * Закрываем соединение
     */
    public function close()
    {
        curl_close($this->curl);
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
     * @throws LengthRequiredHttpException|\LengthException
     *
     * @param bool $strict Исключение если не удалось определить размер файла (не найден Content-Length)
     * @return Response
     */
    public function checkFileSize($strict = true)
    {
        $this->setOpt(CURLOPT_NOBODY, true);
        $response = $this->exec();

        $length = $response->headers->get('Content-Length');
        if (null === $length && true === $strict) {
            throw new LengthRequiredHttpException('Не удалось определить размер файла');
        }

        $maxLength = $this->container->getParameter('wapinet_max_download_filesize');
        if ($length > $maxLength) {
            throw new \LengthException('Размер файла превышает максимально допустимый');
        }

        $this->setOpt(CURLOPT_NOBODY, false);

        return $response;
    }


    /**
     * @param string $rawHeaders
     *
     * @see http://php.net/manual/ru/function.http-parse-headers.php#112986
     * @return array
     */
    protected function parseHeaders($rawHeaders)
    {
        if (!function_exists('http_parse_headers')) {
            $headers = array();
            $key = '';

            foreach (explode("\n", $rawHeaders) as $h) {
                $h = explode(':', $h, 2);

                if (isset($h[1])) {
                    if (!isset($headers[$h[0]])) {
                        $headers[$h[0]] = trim($h[1]);
                    } elseif (is_array($headers[$h[0]])) {
                        $headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1])));
                    } else {
                        $headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1])));
                    }

                    $key = $h[0];
                } else {
                    if (substr($h[0], 0, 1) == "\t") {
                        $headers[$key] .= "\r\n\t".trim($h[0]);
                    } elseif (!$key) {
                        $headers[0] = trim($h[0]);
                    }
                }
            }

            return $headers;
        }

        return http_parse_headers($rawHeaders);
    }


    // загрузка файлов
    // @see https://github.com/kriswallsmith/Buzz/blob/master/lib/Buzz/Client/AbstractCurl.php#L101
    /**
     * Returns a value for the CURLOPT_POSTFIELDS option.
     *
     * @return string|array A post fields value
     */
    /*
    private static function getPostFields(RequestInterface $request)
    {
        if (!$request instanceof FormRequestInterface) {
            return $request->getContent();
        }

        $fields = $request->getFields();
        $multipart = false;

        foreach ($fields as $name => $value) {
            if ($value instanceof FormUploadInterface) {
                $multipart = true;

                if ($file = $value->getFile()) {
                    // replace value with upload string
                    $fields[$name] = '@'.$file;

                    if ($contentType = $value->getContentType()) {
                        $fields[$name] .= ';type='.$contentType;
                    }
                    if (basename($file) != $value->getFilename()) {
                        $fields[$name] .= ';filename='.$value->getFilename();
                    }
                } else {
                    return $request->getContent();
                }
            }
        }

        return $multipart ? $fields : http_build_query($fields, '', '&');
    }
    */






    /**
     * Получаем результат
     *
     * @throws RequestException
     * @return Response
     */
    public function exec()
    {
        if ($this->headers) {
            $this->setOpt(CURLOPT_HTTPHEADER, $this->headers);
        }
        if ($this->postData) {
            $this->sendPostData();
        }

        $out = curl_exec($this->curl);
        if (false === $out) {
            throw new RequestException(curl_error($this->curl), curl_errno($this->curl));
        }
        $size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        // заголовки
        $headers = $this->parseHeaders(rtrim(substr($out, 0, $size)));
        // тело
        $content = substr($out, $size);
        $content = (false === $content ? null : $content);

        return new Response($content, $status, $headers);
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
