<?php

namespace WapinetUploaderBundle\Form\DataTransformer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use WapinetUploaderBundle\Entity\FileContent;
use WapinetUploaderBundle\Entity\FileUrl;

class FileUrlDataTransformer implements DataTransformerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var bool
     */
    protected $required;


    /**
     * @param ContainerInterface $container
     * @param bool               $required
     */
    public function __construct(ContainerInterface $container, $required = true)
    {
        $this->container = $container;
        $this->required = $required;
    }

    /**
     * @param File|null $fileDataFromDb
     *
     * @return array|null
     *
     * @see https://github.com/dustin10/VichUploaderBundle/issues/27
     */
    public function transform($fileDataFromDb)
    {
        if ($fileDataFromDb instanceof File) {
            return array(
                'web_path' => \str_replace('\\', '//', \mb_substr($fileDataFromDb->getPathname(), \mb_strlen($this->container->get('kernel')->getWebDir()))),
                'file_url' => $fileDataFromDb
            );
        } elseif ($fileDataFromDb instanceof FileContent) {
            return array(
                'web_path' => 'data:' . $fileDataFromDb->getMimeType() . ';base64,' . \base64_encode($fileDataFromDb->getContent()),
                'file_url' => $fileDataFromDb
            );
        }

        return null;
    }


    /**
     * @param array $fileDataFromForm
     * @return UploadedFile|FileUrl|null
     * @throws TransformationFailedException|InvalidArgumentException
     */
    public function reverseTransform($fileDataFromForm)
    {
        $uploadedFile = $this->getUploadedFile($fileDataFromForm);

        if (null === $uploadedFile && true === $this->required) {
            throw new TransformationFailedException('Не заполнено обязательное поле');
        }
        // TODO: вероятно эта проверка не нужна
        if (null !== $uploadedFile && true !== $uploadedFile->isValid()) {
            throw new TransformationFailedException('Ошибка при загрузке файла');
        }

        return $uploadedFile;
    }


    /**
     * @param array $fileDataFromForm
     * @return null|FileUrl
     */
    protected function getUploadedFile(array $fileDataFromForm)
    {
        $uploadedFile = null;

        if ($fileDataFromForm['file']) {
            // UploadedFile|string
            $uploadedFile = $fileDataFromForm['file'];
            // TODO: вероятно эта проверка не нужна
            if (!$uploadedFile instanceof UploadedFile) {
                throw new TransformationFailedException('Ошибка при загрузке файла');
            }
        }

        if ($fileDataFromForm['url']) {
            $curl = $this->container->get('curl');
            $curl->init($fileDataFromForm['url']);
            $curl->addBrowserHeaders();
            $curl->acceptRedirects();
            $responseHead = $curl->checkFileSize(false);

            if (!$responseHead->isSuccessful() && !$responseHead->isRedirection()) {
                throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $responseHead->getStatusCode() . ')');
            }

            $temp = \tempnam($this->container->get('kernel')->getTmpDir(), 'file_url');
            if (false === $temp) {
                throw new TransformationFailedException('Не удалось создать временный файл');
            }
            $f = \fopen($temp, 'w');
            if (false === $f) {
                throw new TransformationFailedException('Не удалось открыть временный файл на запись');
            }

            $curl->setOpt(\CURLOPT_HEADER, false);
            $curl->setOpt(\CURLOPT_FILE, $f);

            $responseBody = $curl->exec();
            $curl->close();
            \fclose($f);

            if (!$responseBody->isSuccessful()) {
                throw new TransformationFailedException('Не удалось скачать файл по ссылке (HTTP код: ' . $responseBody->getStatusCode() . ')');
            }


            $contentType = $responseHead->headers->get('Content-Type');
            if (\is_array($contentType)) {
                $contentType = \end($contentType);
            }
            $contentLength = $responseHead->headers->get('Content-Length');
            if (\is_array($contentLength)) {
                $contentLength = \end($contentLength);
            }

            $uploadedFile = new FileUrl(
                $temp,
                $this->getOriginalName($responseHead->headers, $fileDataFromForm['url']),
                $contentType,
                $contentLength
            );
        }

        return $uploadedFile;
    }


    /**
     * @param ResponseHeaderBag $headers
     * @param string $url
     * @return string
     */
    protected function getOriginalName(ResponseHeaderBag $headers, $url)
    {
        $contentDisposition = $headers->get('Content-Disposition');
        if (\is_array($contentDisposition)) {
            $contentDisposition = \end($contentDisposition);
        }
        if ($contentDisposition) {
            $tmpName = $this->parseContentDisposition($contentDisposition);
            if (null !== $tmpName) {
                return $tmpName;
            }
        }

        $location = $headers->get('Location');
        if (\is_array($location)) {
            $location = \end($location);
        }
        if ($location) {
            return \parse_url($location, PHP_URL_PATH);
        }

        return \parse_url($url, PHP_URL_PATH);
    }


    /**
     * @param string $contentDisposition
     * @return null|string
     */
    private function parseContentDisposition($contentDisposition)
    {
        $tmpName = \explode('=', $contentDisposition, 2);
        if (isset($tmpName[1])) {
            $tmpName = \trim($tmpName[1], '";\'');

            $utf8Prefix = 'utf-8\'\''; // utf-8\'\'' . \rawurlencode($var)
            $utf8BPrefix = '=?UTF-8?B?'; // =?UTF-8?B?' . \base64_encode($var) . '?=
            $utf8BPostfix = '?='; // =?UTF-8?B?' . \base64_encode($var) . '?=

            if (0 === \stripos($tmpName, $utf8Prefix)) {
                $tmpName = \substr($tmpName, \strlen($utf8Prefix));
                $tmpName = \rawurldecode($tmpName);

                return $tmpName;
            }

            if (0 === \stripos($tmpName, $utf8BPrefix)) {
                $tmpName = \substr($tmpName, \strlen($utf8BPrefix));
                $tmpName = \substr($tmpName, 0, -\strlen($utf8BPostfix));
                $tmpName = \base64_decode($tmpName);

                return $tmpName;
            }

            return $tmpName;
        }

        return null;
    }
}
