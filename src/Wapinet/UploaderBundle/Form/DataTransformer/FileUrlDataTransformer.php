<?php

namespace Wapinet\UploaderBundle\Form\DataTransformer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wapinet\UploaderBundle\Entity\FileContent;
use Wapinet\UploaderBundle\Entity\FileUrl;

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
                'web_path' => str_replace('\\', '//', mb_substr($fileDataFromDb->getPathName(), mb_strlen($this->container->get('kernel')->getWebDir()))),
                'file_url' => $fileDataFromDb
            );
        } elseif ($fileDataFromDb instanceof FileContent) {
            return array(
                'web_path' => 'data:' . $fileDataFromDb->getMimeType() . ';base64,' . base64_encode($fileDataFromDb->getContent()),
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
        $uploadedFile = null;

        if ($fileDataFromForm['file']) {
            // UploadedFile
            $uploadedFile = $fileDataFromForm['file'];
        }

        if ($fileDataFromForm['url']) {
            $curl = $this->container->get('curl');
            $curl->setOpt(CURLOPT_URL, $fileDataFromForm['url']);
            $curl->addBrowserHeaders();
            $responseHeaders = $curl->checkFileSize();

            $temp = tempnam(\AppKernel::getTmpDir(), 'file_url');
            $f = fopen($temp, 'w');

            $curl->setOpt(CURLOPT_HEADER, false);
            $curl->setOpt(CURLOPT_FILE, $f);

            $curl->exec();
            $curl->close();
            fclose($f);

            $uploadedFile =  new FileUrl(
                $temp,
                $fileDataFromForm['url'],
                $responseHeaders->headers->get('Content-Type'),
                $responseHeaders->headers->get('Content-Length')
            );
        }

        if (null === $uploadedFile && true === $this->required) {
            throw new TransformationFailedException('Не заполнено обязательное поле');
        }
        // TODO: вероятно эта проверка не нужна
        if (null !== $uploadedFile && true !== $uploadedFile->isValid()) {
            throw new TransformationFailedException('Ошибка при загрузке файла');
        }

        return $uploadedFile;
    }
}
