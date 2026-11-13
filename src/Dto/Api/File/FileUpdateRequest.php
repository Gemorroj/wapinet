<?php

namespace App\Dto\Api\File;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FileUpdateRequest
{
    /**
     * @param list<string>|null $tags
     */
    public function __construct(
        #[Assert\Length(max: 5000)]
        public ?string $description = null,
        #[Assert\Count(max: 30)]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\Length(max: 255),
        ])]
        public ?array $tags = null,
        #[Assert\Length(max: 100)]
        public ?string $password = null,
        #[Assert\File(maxSize: '50M')]
        #[OA\Property(type: 'string', format: 'binary', nullable: true)]
        public ?UploadedFile $file = null,
        #[Assert\Url(requireTld: true)]
        #[OA\Property(type: 'string', format: 'uri', nullable: true)]
        public ?string $url = null,
        public ?bool $hidden = null,
    ) {
    }
}
