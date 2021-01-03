<?php

namespace App\Entity\File;

/**
 * File content.
 */
class FileContent
{
    /**
     * @var string
     */
    protected $content;
    /**
     * @var string|null
     */
    protected $mimeType;
    /**
     * @var string|null
     */
    protected $basename;
    /**
     * @var int
     */
    protected $size;

    /**
     * @param string|null $mimeType
     * @param string|null $basename
     */
    public function __construct(string $content, $mimeType = null, $basename = null)
    {
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->basename = $basename;
        $this->size = \strlen($content);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return string|null
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->getSize() > 0;
    }

    /**
     * Returns the original file name.
     *
     * It is extracted from the request from which the file has been uploaded.
     * Then is should not be considered as a safe value.
     *
     * @return string|null The original name
     */
    public function getClientOriginalName()
    {
        return $this->getBasename();
    }
}
