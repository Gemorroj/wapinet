<?php

namespace Wapinet\Bundle\Form\DataTransformer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\AbstractType;
use Wapinet\Bundle\Entity\FileUrl;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUrlDataTransformer implements DataTransformerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function transform($fileDataFromDb)
    {
        return $fileDataFromDb;
    }


    /**
     * array with 2 items - file (UploadedFile) and url (text)
     *
     * @param array $fileDataFromForm
     * @return FileUrl|UploadedFile|null
     */
    public function reverseTransform($fileDataFromForm)
    {
        if ($fileDataFromForm['file']) {
            // UploadedFile
            return $fileDataFromForm['file'];
        }

        if ($fileDataFromForm['url']) {
            $curl = $this->container->get('curl_helper');
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

            return new FileUrl($temp, $fileDataFromForm['url'], $responseHeaders->headers->get('Content-Type'), $responseHeaders->headers->get('Content-Length'));
        }

        return null;
    }
}
