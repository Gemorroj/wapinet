<?php

declare(strict_types=1);

namespace App\Dto\Api\AudioTags;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class AudioTagsCreateRequest
{
    /**
     * @param list<UploadedFile> $files
     */
    public function __construct(
        #[Assert\All([
            new Assert\Audio(
                maxSize: '50M',
                mimeTypesMessage: 'Файл не является аудиофайлом.',
                uploadIniSizeErrorMessage: 'Файл слишком большой.',
                uploadFormSizeErrorMessage: 'Файл слишком большой.',
            ),
        ])]
        #[Assert\Count(max: 1, maxMessage: 'Можно загрузить только один файл.')]
        #[OA\Property(
            type: 'array',
            items: new OA\Items(type: 'string', format: 'binary'),
            nullable: true,
        )]
        public array $files = [],
        #[Assert\Url(requireTld: true)]
        #[OA\Property(type: 'string', format: 'uri', nullable: true)]
        public ?string $url = null,
    ) {
    }

    #[Assert\Callback]
    public function validateSource(ExecutionContextInterface $context): void
    {
        $hasFiles = [] !== $this->files;
        $hasUrl = null !== $this->url && '' !== $this->url;

        if (!$hasFiles && !$hasUrl) {
            $context->buildViolation('Выберите файл или укажите ссылку.')->addViolation();

            return;
        }

        if ($hasFiles && $hasUrl) {
            $context->buildViolation('Выберите файл или ссылку, но не одновременно.')->addViolation();
        }
    }
}
