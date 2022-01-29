<?php

namespace App\Entity\File;

class FileContent
{
    private int $size;

    public function __construct(
        private string $content,
        private ?string $mimeType = null,
        private ?string $basename = null,
    ) {
        $this->size = \strlen($content);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getBasename(): ?string
    {
        return $this->basename;
    }

    public function getSize(): int
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
