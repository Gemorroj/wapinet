<?php

namespace App\Form\DataTransformer;

use App\Entity\File\FileContent;
use App\Entity\File\FileUrl;
use Riverline\MultiPartParser\StreamedPart;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileUrlDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private HttpClientInterface $httpClient,
        private bool $required = true
    ) {
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
            $response = $this->httpClient->request('GET', $fileDataFromForm['url']);
            try {
                $headers = $response->getHeaders();
                $info = $response->getInfo();
                $maxFilesize = UploadedFile::getMaxFilesize();
                if ($info['download_content_length'] > $maxFilesize) {
                    throw new \LengthException('Размер файла превышает максимально допустимый');
                }
            } catch (HttpExceptionInterface $e) {
                throw new \Exception('Не удалось получить данные (HTTP код: '.$e->getResponse()->getStatusCode().')');
            }

            $temp = \tempnam($this->parameterBag->get('kernel.tmp_dir'), 'file_url');
            if (false === $temp) {
                throw new TransformationFailedException('Не удалось создать временный файл');
            }
            $f = \fopen($temp, 'w');
            if (false === $f) {
                throw new TransformationFailedException('Не удалось открыть временный файл на запись');
            }

            foreach ($this->httpClient->stream($response) as $chunk) {
                \fwrite($f, $chunk->getContent());
            }
            \fclose($f);

            $uploadedFile = new FileUrl(
                $temp,
                $this->getOriginalName($headers, $fileDataFromForm['url']),
                $info['content_type'],
                $info['download_content_length'] > 0 ? (int) $info['download_content_length'] : null
            );
        }

        return $uploadedFile;
    }

    /**
     * @param string[][] $headers
     */
    protected function getOriginalName(array $headers, string $url, string $default = 'index.html'): string
    {
        $contentDisposition = $headers['content-disposition'][0] ?? null;
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

        $location = $headers['location'][0] ?? null;
        if ($location) {
            $locationPath = \parse_url($location, \PHP_URL_PATH);
            if (null !== $locationPath && '/' !== $locationPath) {
                return $locationPath;
            }
        }

        return $default;
    }
}
