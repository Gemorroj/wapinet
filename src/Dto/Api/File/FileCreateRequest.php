<?php

namespace App\Dto\Api\File;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class FileCreateRequest
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        #[Assert\File(maxSize: '50M')]
        #[OA\Property(type: 'string', format: 'binary', nullable: true)]
        public ?UploadedFile $file = null,
        #[Assert\Url(requireTld: true)]
        #[OA\Property(type: 'string', format: 'uri', nullable: true)]
        public ?string $url = null,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $description = '',
        #[Assert\Count(max: 30)]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\Length(max: 255),
        ])]
        public array $tags = [],
        #[Assert\Length(max: 100)]
        public ?string $password = null,
        #[Assert\Length(max: 4096)]
        public ?string $captchaToken = null,
    ) {
    }

    #[Assert\Callback]
    public function validateSource(ExecutionContextInterface $context): void
    {
        $hasFile = null !== $this->file;
        $hasUrl = null !== $this->url && '' !== $this->url;

        if (!$hasFile && !$hasUrl) {
            $context->buildViolation('Укажите файл или ссылку.')->addViolation();

            return;
        }

        if ($hasFile && $hasUrl) {
            $context->buildViolation('Укажите только файл или только ссылку.')->addViolation();
        }
    }
}
