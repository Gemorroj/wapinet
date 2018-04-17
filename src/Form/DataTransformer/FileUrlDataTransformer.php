<?php

namespace App\Form\DataTransformer;

use App\Entity\File\FileContent;
use App\Entity\File\FileUrl;
use Riverline\MultiPartParser\Part;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
            return [
                'web_path' => \str_replace('\\', '//', \mb_substr($fileDataFromDb->getPathname(), \mb_strlen($this->container->get('kernel')->getPublicDir()))),
                'file_url' => $fileDataFromDb
            ];
        }
        if ($fileDataFromDb instanceof FileContent) {
            return [
                'web_path' => 'data:' . $fileDataFromDb->getMimeType() . ';base64,' . \base64_encode($fileDataFromDb->getContent()),
                'file_url' => $fileDataFromDb
            ];
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
            $f = \fopen($temp, 'wb');
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

            $uploadedFile = new FileUrl(
                $temp,
                $this->getOriginalName($responseHead->headers, $fileDataFromForm['url']),
                $responseHead->headers->get('Content-Type'),
                $responseHead->headers->get('Content-Length')
            );
        }

        return $uploadedFile;
    }


    /**
     * @param ResponseHeaderBag $headers
     * @param string $url
     * @param string $default
     * @return string
     */
    protected function getOriginalName(ResponseHeaderBag $headers, string $url, string $default = 'index.html') : string
    {
        $contentDisposition = $headers->get('Content-Disposition');
        if ($contentDisposition) {
            $tmpName = Part::getHeaderOptions($contentDisposition);
            if (isset($tmpName['filename*']) && $tmpName['filename*']) {
                $encodedFilename = $this->decodeRfc5987($tmpName['filename*']);
                if ($encodedFilename) {
                    return $encodedFilename;
                }
            }
            if (isset($tmpName['filename']) && $tmpName['filename']) {
                return $tmpName['filename'];
            }
        }

        $path = \parse_url($url, PHP_URL_PATH);
        if (null !== $path && '/' !== $path) {
            return $path;
        }

        $location = $headers->get('Location');
        if ($location) {
            $locationPath = \parse_url($location, PHP_URL_PATH);
            if (null !== $locationPath && '/' !== $locationPath) {
                return $locationPath;
            }
        }

        return $default;
    }


    /**
     * Decodes filenames given in the content-disposition header according
     * to RFC5987, such as filename*=utf-8''filename.png. Note that the
     * language sub-component is defined in RFC5646, and that the filename
     * is URL encoded (in the charset specified)
     *
     * @see https://github.com/osTicket/osTicket/blob/v1.10.2/include/class.format.php#L70
     * @param string $encodedFilename
     * @return string|null
     */
    private function decodeRfc5987($encodedFilename): ?string
    {
        if (\preg_match("/(?P<charset>[\w!#$%&+^_`{}~-]+)'(?P<language>[\w-]*)'(?P<filename>.*)$/", $encodedFilename, $match)) {
            return \mb_convert_encoding(\rawurldecode($match['filename']), 'UTF-8', $match['charset']);
        }
        return null;
    }
}
