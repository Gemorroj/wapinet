<?php

namespace Wapinet\Bundle\Form\DataTransformer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Wapinet\Bundle\Entity\File;
use Wapinet\Bundle\Entity\FileUrl;

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
     * @var bool
     */
    protected $save;
    /**
     * @var string|null
     */
    protected $savePath;

    public function __construct(ContainerInterface $container, $required = true, $save = false, $savePath = null)
    {
        $this->container = $container;
        $this->required = $required;
        $this->save = $save;
        $this->savePah = $savePath;
    }


    public function transform($fileDataFromDb)
    {
        return $fileDataFromDb;
    }


    /**
     * @param array $fileDataFromForm
     * @return File|null
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

        $file = new File($uploadedFile);

        if (true === $this->save) {
            if (null === $this->savePah) {
                throw new InvalidArgumentException('Не указана директория для сохранения файла');
            }
            $fileNamer = $this->container->get('file_namer');
            $fileNamer->setFile($uploadedFile);

            $file->setDirectory($fileNamer->getDirectory());
            $file->setFilename($fileNamer->getFilename());
            $file->move($this->savePah . '/' . $file->getDirectory(), $file->getFilename());
        }

        return $file;
    }
}
