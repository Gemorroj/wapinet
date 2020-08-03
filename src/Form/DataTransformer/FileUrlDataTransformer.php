<?php

namespace App\Form\DataTransformer;

use App\Entity\File\FileContent;
use App\Entity\File\FileUrl;
use App\Service\Curl;
use Riverline\MultiPartParser\StreamedPart;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileUrlDataTransformer implements DataTransformerInterface
{
    private ParameterBagInterface $parameterBag;
    private bool $required;
    private Curl $curl;

    public function __construct(ParameterBagInterface $parameterBag, Curl $curl, bool $required = true)
    {
        $this->parameterBag = $parameterBag;
        $this->curl = $curl;
        $this->required = $required;
    }

    /**
     * @param File|FileContent|null $fileDataFromDb
     *
     * @see https://github.com/dustin10/VichUploaderBundle/issues/27
     */
    public function transform($fileDataFromDb): ?array
    {
        if ($fileDataFromDb instanceof File) {
            return [
                'web_path' => \str_replace('\\', '//', \mb_substr($fileDataFromDb->getPathname(), \mb_strlen($this->parameterBag->get('kernel.project_dir').'/public'))),
                'file_url' => $fileDataFromDb,
            ];
        }
        if ($fileDataFromDb instanceof FileContent) {
            return [
                'web_path' => 'data:'.$fileDataFromDb->getMimeType().';base64,'.\base64_encode($fileDataFromDb->getContent()),
                'file_url' => $fileDataFromDb,
            ];
        }

        return null;
    }

    /**
     * @param array $fileDataFromForm
     *
     * @throws TransformationFailedException|InvalidArgumentException
     *
     * @return UploadedFile|FileUrl|null
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
     * @return FileUrl|UploadedFile|null
     */
    protected function getUploadedFile(array $fileDataFromForm)
    {
        $uploadedFile = null;

        if ($fileDataFromForm['file']) {
            // UploadedFile|string
            $uploadedFile = $fileDataFromForm['file'];
            // TODO: вероятно эта проверка не нужна
            if (!($uploadedFile instanceof UploadedFile)) {
                throw new TransformationFailedException('Ошибка при загрузке файла');
            }
        }

        if ($fileDataFromForm['url']) {
            $this->curl->init($fileDataFromForm['url']);
            $this->curl->addBrowserHeaders();
            $this->curl->acceptRedirects();
            $responseHead = $this->curl->checkFileSize(false);

            if (!$responseHead->isSuccessful() && !$responseHead->isRedirection()) {
                throw new \RuntimeException('Не удалось получить данные (HTTP код: '.$responseHead->getStatusCode().')');
            }

            $temp = \tempnam($this->parameterBag->get('kernel.tmp_dir'), 'file_url');
            if (false === $temp) {
                throw new TransformationFailedException('Не удалось создать временный файл');
            }
            $f = \fopen($temp, 'w');
            if (false === $f) {
                throw new TransformationFailedException('Не удалось открыть временный файл на запись');
            }

            $this->curl->setOpt(\CURLOPT_HEADER, false);
            $this->curl->setOpt(\CURLOPT_FILE, $f);

            $responseBody = $this->curl->exec();
            $this->curl->close();
            \fclose($f);

            if (!$responseBody->isSuccessful()) {
                throw new TransformationFailedException('Не удалось скачать файл по ссылке (HTTP код: '.$responseBody->getStatusCode().')');
            }

            $uploadedFile = new FileUrl(
                $temp,
                $this->getOriginalName($responseHead->headers, $fileDataFromForm['url']),
                $responseHead->headers->get('Content-Type'),
                $responseHead->headers->has('Content-Length') ? (int) $responseHead->headers->get('Content-Length') : null
            );
        }

        return $uploadedFile;
    }

    protected function getOriginalName(ResponseHeaderBag $headers, string $url, string $default = 'index.html'): string
    {
        $contentDisposition = $headers->get('Content-Disposition');
        if ($contentDisposition) {
            $tmpName = StreamedPart::getHeaderOption($contentDisposition, 'filename');
            if ($tmpName) {
                return $tmpName;
            }
        }

        $path = \parse_url($url, \PHP_URL_PATH);
        if (null !== $path && '/' !== $path) {
            return $path;
        }

        $location = $headers->get('Location');
        if ($location) {
            $locationPath = \parse_url($location, \PHP_URL_PATH);
            if (null !== $locationPath && '/' !== $locationPath) {
                return $locationPath;
            }
        }

        return $default;
    }
}
