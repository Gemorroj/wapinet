<?php

declare(strict_types=1);

namespace App\Dto\Api\AudioTags;

final readonly class AudioTagsTagsResponse
{
    public function __construct(
        public string $title = '',
        public string $albumArtist = '',
        public string $artist = '',
        public string $album = '',
        public string $year = '',
        public string $trackNumber = '',
        public string $urlUser = '',
        public string $genre = '',
        public string $comment = '',
        public string $composer = '',
        public string $discNumber = '',
        public string $copyright = '',
        public string $lyrics = '',
        public string $bpm = '',
        public string $isrc = '',
        public bool $hasPicture = false,
    ) {
    }
}
