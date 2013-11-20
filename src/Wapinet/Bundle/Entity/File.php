<?php

namespace Wapinet\Bundle\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
/**
 * File
 */
class File extends BaseFile implements \Serializable
{
    /**
     * @var string|null
     */
    protected $directory;

    /**
     * @var string|null
     */
    protected $filename;

    /**
     * @var string|null
     */
    protected $mimeType;

    /**
     * @var int|null
     */
    protected $size;

    /**
     * @var null|UploadedFile
     */
    private $uploadedFile;


    /**
     * @param UploadedFile|string $file
     */
    public function __construct($file)
    {
        if ($file instanceof UploadedFile) {
            $this->uploadedFile = $file;
            parent::__construct($file->getPathname());
        } else {
            parent::__construct($file);
        }
        $this->size = $this->getSize();
        $this->mimeType = $this->getMimeType();
    }


    /**
     * @param string $directory
     * @param null|string $name
     * @return BaseFile
     */
    public function move($directory, $name = null)
    {
        if (null !== $this->uploadedFile) {
            return $this->uploadedFile->move($directory, $name);
        } else {
            return parent::move($directory, $name);
        }
    }


    /**
     * Get directory
     *
     * @return string|null
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set directory
     *
     * @param string $directory
     * @return File
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return File
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get mime type
     *
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Get size
     *
     * @return int|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getDirectory() . '/' . $this->getFilename();
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            'directory' => $this->getDirectory(),
            'filename' => $this->getFilename(),
            'mimeType' => $this->getMimeType(),
            'size' => $this->getSize(),
        ));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->setDirectory($data['directory']);
        $this->setFilename($data['filename']);
        $this->size = $data['size'];
        $this->mimeType = $data['mimeType'];
    }
}
