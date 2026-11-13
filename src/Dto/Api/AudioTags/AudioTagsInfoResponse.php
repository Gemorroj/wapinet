<?php

declare(strict_types=1);

namespace App\Dto\Api\AudioTags;

final readonly class AudioTagsInfoResponse
{
    /**
     * @param list<string>                    $tagFormats
     * @param list<AudioTagsOtherTagResponse> $otherTags
     */
    public function __construct(
        public string $id,
        public string $originalName,
        public int $size,
        public ?float $duration,
        public string $format,
        public ?int $bitrate,
        public ?string $bitrateMode,
        public ?int $sampleRate,
        public ?string $channelMode,
        public array $tagFormats,
        public AudioTagsTagsResponse $tags,
        public array $otherTags,
        public bool $canWrite,
        public ?string $readOnlyReason,
    ) {
    }
}
