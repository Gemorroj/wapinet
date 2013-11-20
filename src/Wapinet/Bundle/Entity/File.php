<?php

namespace Wapinet\Bundle\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
/**
 * File
 */
class File extends \ArrayIterator implements \Serializable
{
    /**
     * @var string|null
     */
    protected $baseDirectory;
    /**
     * @var string|null
     */
    protected $basePublicDirectory;
    /**
     * @var string|null
     */
    protected $directory;

    /**
     * @var string|null
     */
    protected $filename;

    /**
     * @var BaseFile|null
     */
    protected $file;

    /**
     * @param UploadedFile|null $file
     */
    public function __construct(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * @return BaseFile|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
        return (null !== $this->file);
    }

    /**
     * Get base directory
     *
     * @return string|null
     */
    public function getBaseDirectory()
    {
        return $this->baseDirectory;
    }

    /**
     * Set base directory
     *
     * @param string $baseDirectory
     * @return File
     */
    public function setBaseDirectory($baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;

        return $this;
    }

    /**
     * Get base public directory
     *
     * @return string|null
     */
    public function getBasePublicDirectory()
    {
        return $this->basePublicDirectory;
    }

    /**
     * Set base public directory
     *
     * @param string $basePublicDirectory
     * @return File
     */
    public function setBasePublicDirectory($basePublicDirectory)
    {
        $this->basePublicDirectory = $basePublicDirectory;

        return $this;
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
     * @return string
     */
    public function getPublicPath()
    {
        return $this->getBasePublicDirectory() .'/' . $this->getPath();
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        return $this->getBaseDirectory() .'/' . $this->getPath();
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
        if (null == $this->getFile()) {
            return serialize(null);
        }

        return serialize(array(
            'base_directory' => $this->getBaseDirectory(),
            'base_public_directory' => $this->getBasePublicDirectory(),
            'directory' => $this->getDirectory(),
            'filename' => $this->getFilename(),
        ));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        if ($data) {
            $this->setBaseDirectory($data['base_directory']);
            $this->setBasePublicDirectory($data['base_public_directory']);
            $this->setDirectory($data['directory']);
            $this->setFilename($data['filename']);
            $this->file = new BaseFile($data['base_directory'] . '/' . $data['directory'] . '/' . $data['filename']);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getLocalPath();
    }
}
