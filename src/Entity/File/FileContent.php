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
    private $content;
    /**
     * @var string|null
     */
    private $mimeType;
    /**
     * @var string|null
     */
    private $basename;
    /**
     * @var int
     */
    private $size;

    public function __construct(string $content, ?string $mimeType = null, ?string $basename = null)
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

    public function isValid(): bool
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
    public function getClientOriginalName(): ?string
    {
        return $this->getBasename();
    }
}
