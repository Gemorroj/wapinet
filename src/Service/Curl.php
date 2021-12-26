<?php

namespace App\Service;

use App\Exception\RequestException;
use const CURLINFO_HEADER_SIZE;
use const CURLINFO_HTTP_CODE;
use const CURLOPT_ENCODING;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_NOBODY;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_URL;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\LengthRequiredHttpException;

/**
 * CURL хэлпер
 */
class Curl
{
    /**
     * @var \CurlHandle
     */
    private $curl;

    /**
     * @var string[]
     */
    protected static array $browserHeaders = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Connection: Close',
        'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:84.0) Gecko/20100101 Firefox/84.0',
    ];
    protected array $headers = [];
    protected array $postData = [];

    /**
     * Инициализация.
     *
     * @return Curl
     */
    public function init(?string $url = null): self
    {
        $this->curl = \curl_init();

        $this->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $this->setOpt(CURLOPT_SSL_VERIFYHOST, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_HEADER, true);

        if (null !== $url) {
            $this->setUrl($url);
        }

        return $this;
    }

    /**
     * Закрываем соединение.
     */
    public function close(): void
    {
        \curl_close($this->curl);
    }

    /**
     * Задаем опцию.
     *
     * @param mixed $value
     *
     * @return Curl
     */
    public function setOpt(int $key, $value): self
    {
        \curl_setopt($this->curl, $key, $value);

        return $this;
    }

    /**
     * Задаем url.
     *
     * @return Curl
     */
    public function setUrl(string $value): self
    {
        \curl_setopt($this->curl, CURLOPT_URL, $value);

        return $this;
    }

    /**
     * Следовать перенаправлениям
     *
     * @param int $maxRedirects
     *
     * @return Curl
     */
    public function acceptRedirects(?int $maxRedirects = null): self
    {
        \curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        if (null !== $maxRedirects) {
            \curl_setopt($this->curl, CURLOPT_MAXREDIRS, $maxRedirects);
        }

        return $this;
    }

    /**
     * @param bool $strict Исключение если не удалось определить размер файла (не найден Content-Length)
     *
     *@throws LengthRequiredHttpException|\LengthException
     */
    public function checkFileSize(bool $strict = true): Response
    {
        $this->setOpt(CURLOPT_NOBODY, true);
        $response = $this->exec();

        $length = $response->headers->get('Content-Length');
        if (null === $length && true === $strict) {
            throw new LengthRequiredHttpException('Не удалось определить размер файла');
        }

        $maxLength = UploadedFile::getMaxFilesize();
        if ($length > $maxLength) {
            throw new \LengthException('Размер файла превышает максимально допустимый');
        }

        $this->setOpt(CURLOPT_NOBODY, false);

        return $response;
    }

    /**
     * @see http://php.net/manual/ru/function.http-parse-headers.php#112986
     */
    protected function parseHeaders(string $rawHeaders): array
    {
        if (\function_exists('\http_parse_headers')) {
            return \http_parse_headers($rawHeaders);
        }

        $headers = [];
        $key = '';

        foreach (\explode("\n", $rawHeaders) as $h) {
            $h = \explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = \trim($h[1]);
                } elseif (\is_array($headers[$h[0]])) {
                    $headers[$h[0]] = [...$headers[$h[0]], ...[\trim($h[1])]];
                } else {
                    $headers[$h[0]] = [...[$headers[$h[0]]], ...[\trim($h[1])]];
                }

                $key = $h[0];
            } else {
                if (\str_starts_with($h[0], "\t")) {
                    $headers[$key] .= "\r\n\t".\trim($h[0]);
                } elseif (!$key) {
                    $headers[0] = \trim($h[0]);
                }
            }
        }

        return $headers;
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
     */
    public function exec(): Response
    {
        if ($this->headers) {
            $this->setOpt(CURLOPT_HTTPHEADER, $this->headers);
        }
        if ($this->postData) {
            $this->sendPostData();
        }

        $out = \curl_exec($this->curl);
        if (false === $out) {
            throw new RequestException(\curl_error($this->curl), \curl_errno($this->curl));
        }
        $size = \curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $status = \curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        // заголовки
        $headers = $this->parseHeaders(\rtrim(\mb_substr($out, 0, $size)));
        // тело
        $content = \mb_substr($out, $size);
        $content = (false === $content ? null : $content);

        return new Response($content, $status, $headers);
    }

    /**
     * @return Curl
     */
    protected function sendPostData(): self
    {
        $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->setOpt(CURLOPT_POST, true);
        $post = '';
        foreach ($this->postData as $key => $value) {
            $post .= \rawurlencode($key).'='.\rawurlencode($value).'&';
        }
        $post = \rtrim($post, '&');
        $this->setOpt(CURLOPT_POSTFIELDS, $post);

        return $this;
    }

    /**
     * Получение ресурса CURL.
     *
     * @return \CurlHandle
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * @return Curl
     */
    public function addCompression(): self
    {
        $this->setOpt(CURLOPT_ENCODING, '');

        return $this;
    }

    /**
     * @return Curl
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers[] = $key.': '.$value;

        return $this;
    }

    /**
     * @return Curl
     */
    public function addBrowserHeaders(): self
    {
        $this->headers = \array_merge($this->headers, static::$browserHeaders);

        return $this;
    }

    /**
     * @return Curl
     */
    public function addPostData(string $key, string $value): self
    {
        $this->postData[$key] = $value;

        return $this;
    }
}
