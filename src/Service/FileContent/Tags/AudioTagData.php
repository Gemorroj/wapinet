<?php

declare(strict_types=1);

namespace App\Service\FileContent\Tags;

final readonly class AudioTagData
{
    /**
     * @param array<string, list<string>> $comments
     */
    public function __construct(
        public array $comments,
        public bool $removePicture = false,
        public ?string $pictureData = null,
        public ?string $pictureMime = null,
    ) {
    }
}
