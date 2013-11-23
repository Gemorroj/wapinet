<?php

namespace Wapinet\UploaderBundle\Entity;


/**
 * File content
 */
class FileContent
{
    /**
     * @var string|null
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

    public function __construct($content, $mimeType = null, $basename = null)
    {
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->basename = $basename;
        $this->size = strlen($content);
    }

    /**
     * @return null|string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return null|string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return null|string
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
}
