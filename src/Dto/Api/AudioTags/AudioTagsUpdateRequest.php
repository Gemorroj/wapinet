<?php

declare(strict_types=1);

namespace App\Dto\Api\AudioTags;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class AudioTagsUpdateRequest
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        #[Assert\Length(max: 255)]
        public ?string $albumArtist = null,
        #[Assert\Length(max: 255)]
        public ?string $artist = null,
        #[Assert\Length(max: 255)]
        public ?string $album = null,
        #[Assert\Length(max: 32)]
        public ?string $year = null,
        #[Assert\Length(max: 32)]
        public ?string $trackNumber = null,
        #[Assert\Length(max: 255)]
        public ?string $urlUser = null,
        #[Assert\Length(max: 255)]
        public ?string $genre = null,
        #[Assert\Length(max: 10000)]
        public ?string $comment = null,
        #[Assert\Length(max: 255)]
        public ?string $composer = null,
        #[Assert\Length(max: 32)]
        public ?string $discNumber = null,
        #[Assert\Length(max: 255)]
        public ?string $copyright = null,
        #[Assert\Length(max: 20000)]
        public ?string $lyrics = null,
        #[Assert\Length(max: 16)]
        public ?string $bpm = null,
        #[Assert\Length(max: 32)]
        public ?string $isrc = null,
        public bool $removeOtherTags = false,
        public bool $removePicture = false,
        #[Assert\File(
            maxSize: '5M',
            uploadIniSizeErrorMessage: 'Обложка слишком большая.',
            uploadFormSizeErrorMessage: 'Обложка слишком большая.',
        )]
        #[OA\Property(type: 'string', format: 'binary', nullable: true)]
        public ?UploadedFile $picture = null,
        #[Assert\Url(requireTld: true)]
        #[OA\Property(type: 'string', format: 'uri', nullable: true)]
        public ?string $pictureUrl = null,
    ) {
    }

    #[Assert\Callback]
    public function validatePicture(ExecutionContextInterface $context): void
    {
        if (null !== $this->picture && null !== $this->pictureUrl && '' !== $this->pictureUrl) {
            $context->buildViolation('Выберите файл обложки или ссылку, но не одновременно.')->addViolation();
        }
    }
}
