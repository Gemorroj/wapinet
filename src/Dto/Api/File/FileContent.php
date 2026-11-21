<?php

declare(strict_types=1);

namespace App\Dto\Api\File;

final readonly class FileContent
{
    /**
     * @param list<PlaylistEntry>  $playlist
     * @param list<TorrentEntry>   $torrent
     * @param list<FilePermission> $permissions
     */
    public function __construct(
        public bool $playableAudio,
        public bool $playableVideo,
        public bool $conversionPending,
        public array $playlist,
        public array $torrent,
        public array $permissions,
    ) {
    }
}
