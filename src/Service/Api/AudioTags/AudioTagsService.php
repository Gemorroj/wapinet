<?php

declare(strict_types=1);

namespace App\Service\Api\AudioTags;

use App\Dto\Api\AudioTags\AudioTagsCreateRequest;
use App\Dto\Api\AudioTags\AudioTagsInfoResponse;
use App\Dto\Api\AudioTags\AudioTagsOtherTagResponse;
use App\Dto\Api\AudioTags\AudioTagsTagsResponse;
use App\Dto\Api\AudioTags\AudioTagsUpdateRequest;
use App\Service\Api\File\RemoteFileFetcher;
use App\Service\FileContent\Audio\Tag\AudioTagData;
use App\Service\FileContent\Audio\Tag\Flac;
use App\Service\FileContent\Audio\Tag\Ogg;
use App\Service\FileContent\Audio\Tag\Wav;
use App\Service\FileContent\Getid3;

final readonly class AudioTagsService
{
    private const int MAX_AUDIO_SIZE = 52428800;
    private const int MAX_PICTURE_SIZE = 5242880;

    private const array KNOWN_COMMENT_KEYS = [
        'title', 'album_artist', 'albumartist', 'band', 'artist', 'album', 'year', 'date', 'recording_time',
        'track_number', 'track', 'url_user', 'genre', 'comment', 'picture',
        'composer', 'part_of_a_set', 'discnumber', 'disc_number', 'disc',
        'copyright_message', 'copyright', 'unsynchronised_lyric', 'lyrics', 'bpm', 'isrc',
        'metadata_block_picture', 'coverart', 'coverartmime',
    ];

    public function __construct(
        private Getid3 $getid3,
        private AudioTagsStorage $storage,
        private RemoteFileFetcher $remoteFileFetcher,
        private Flac $flac,
        private Ogg $ogg,
        private Wav $wav,
    ) {
    }

    public function create(AudioTagsCreateRequest $request): AudioTagsInfoResponse
    {
        $file = [] !== $request->files
            ? $request->files[0]
            : $this->remoteFileFetcher->fetch((string) $request->url, self::MAX_AUDIO_SIZE);

        $id = $this->storage->store($file);

        try {
            return $this->info($id);
        } catch (\Throwable $exception) {
            $this->storage->remove($id);

            throw $exception;
        }
    }

    public function info(string $id): AudioTagsInfoResponse
    {
        $path = $this->storage->path($id);
        $info = $this->analyze($path);

        if (!isset($info['audio'])) {
            throw new AudioTagsException('Не удалось распознать аудиофайл.');
        }

        $audio = $info['audio'];
        $comments = $info['comments'] ?? [];
        $tagFormats = $this->allowedTagFormats($info);
        $canWrite = [] !== $tagFormats;

        return new AudioTagsInfoResponse(
            $id,
            $this->storage->originalName($id),
            (int) ($info['filesize'] ?? \filesize($path)),
            isset($info['playtime_seconds']) ? (float) $info['playtime_seconds'] : null,
            $this->format($info),
            isset($audio['bitrate']) ? (int) $audio['bitrate'] : null,
            self::stringOrNull($audio['bitrate_mode'] ?? null),
            isset($audio['sample_rate']) ? (int) $audio['sample_rate'] : null,
            self::stringOrNull($audio['channelmode'] ?? null),
            $tagFormats,
            new AudioTagsTagsResponse(
                self::tag($comments, 'title'),
                self::tag($comments, 'album_artist') ?: (self::tag($comments, 'albumartist') ?: self::tag($comments, 'band')),
                self::tag($comments, 'artist'),
                self::tag($comments, 'album'),
                self::tag($comments, 'year') ?: self::tag($comments, 'date'),
                self::tag($comments, 'track_number') ?: self::tag($comments, 'track'),
                self::tag($comments, 'url_user'),
                self::tag($comments, 'genre'),
                self::tag($comments, 'comment'),
                self::tag($comments, 'composer'),
                self::tag($comments, 'part_of_a_set') ?: (self::tag($comments, 'discnumber') ?: (self::tag($comments, 'disc_number') ?: self::tag($comments, 'disc'))),
                self::tag($comments, 'copyright_message') ?: self::tag($comments, 'copyright'),
                self::tag($comments, 'unsynchronised_lyric') ?: self::tag($comments, 'lyrics'),
                self::tag($comments, 'bpm'),
                self::tag($comments, 'isrc'),
                isset($comments['picture'][0]),
            ),
            $this->otherTags($comments),
            $canWrite,
            $canWrite ? null : $this->readOnlyReason($info),
        );
    }

    public function update(string $id, AudioTagsUpdateRequest $request): AudioTagsInfoResponse
    {
        $path = $this->storage->path($id);
        $info = $this->analyze($path);
        $tagFormats = $this->allowedTagFormats($info);

        if ([] === $tagFormats) {
            throw new AudioTagsException($this->readOnlyReason($info));
        }

        $container = $info['fileformat'] ?? null;

        if ('flac' === $container || 'wav' === $container || ('ogg' === $container && 'vorbis' === ($info['audio']['dataformat'] ?? null))) {
            try {
                $data = $this->vorbisTagData($info, $request);

                if ('flac' === $container) {
                    $this->flac->writeTags($path, $data);
                } elseif ('wav' === $container) {
                    $this->wav->writeTags($path, $data);
                } else {
                    $this->ogg->writeTags($path, $data);
                }
            } catch (\RuntimeException $exception) {
                throw new AudioTagsException($exception->getMessage(), 0, $exception);
            }

            return $this->info($id);
        }

        $writer = $this->getid3->getId3Writer();
        $writer->overwrite_tags = true;
        $writer->remove_other_tags = $request->removeOtherTags;
        $writer->tagformats = $tagFormats;
        $writer->filename = $path;
        $writer->tag_data = \array_merge([
            'title' => [$request->title ?? ''],
            'album_artist' => [$request->albumArtist ?? ''],
            'albumartist' => [$request->albumArtist ?? ''],
            'band' => [$request->albumArtist ?? ''],
            'artist' => [$request->artist ?? ''],
            'album' => [$request->album ?? ''],
            'year' => [$request->year ?? ''],
            'date' => [$request->year ?? ''],
            'track_number' => [$request->trackNumber ?? ''],
            'track' => [$request->trackNumber ?? ''],
            'url_user' => [$request->urlUser ?? ''],
            'genre' => [$request->genre ?? ''],
            'comment' => [$request->comment ?? ''],
        ], $this->advancedTagData($info, $request));

        if (!$request->removeOtherTags) {
            $writer->tag_data = \array_merge($writer->tag_data, $this->otherTagData($info));
        }

        if (!$request->removePicture) {
            $this->setPicture($writer, $info, $request);
        }

        if (!$writer->WriteTags()) {
            throw new AudioTagsException(\implode('; ', $writer->errors));
        }

        return $this->info($id);
    }

    public function clearTags(string $id): AudioTagsInfoResponse
    {
        $path = $this->storage->path($id);
        $info = $this->analyze($path);
        $tagFormats = $this->allowedTagFormats($info);

        if ([] === $tagFormats) {
            throw new AudioTagsException($this->readOnlyReason($info));
        }

        try {
            if ('flac' === ($info['fileformat'] ?? null)) {
                $this->flac->clearTags($path);

                return $this->info($id);
            }

            if ('wav' === ($info['fileformat'] ?? null)) {
                $this->wav->clearTags($path);

                return $this->info($id);
            }

            if ('ogg' === ($info['fileformat'] ?? null) && 'vorbis' === ($info['audio']['dataformat'] ?? null)) {
                $this->ogg->clearTags($path);

                return $this->info($id);
            }
        } catch (\RuntimeException $exception) {
            throw new AudioTagsException($exception->getMessage(), 0, $exception);
        }

        $writer = $this->getid3->getId3Writer();
        $writer->filename = $path;
        $deleteFormats = \array_values(\array_unique(\array_map(
            static fn (string $format): string => \str_starts_with($format, 'id3v2') ? 'id3v2' : $format,
            $tagFormats,
        )));

        if (!$writer->DeleteTags($deleteFormats)) {
            throw new AudioTagsException(\implode('; ', $writer->errors));
        }

        return $this->info($id);
    }

    /**
     * @return array{data: string, mime: string}
     */
    public function cover(string $id): array
    {
        $cover = $this->getid3->getCover($this->storage->path($id));

        if (null === $cover) {
            throw new AudioTagsNotFoundException('Обложка не найдена.');
        }

        return $cover;
    }

    public function delete(string $id): void
    {
        $this->storage->remove($id);
    }

    /**
     * @param array<string, mixed> $info
     */
    private function vorbisTagData(array $info, AudioTagsUpdateRequest $request): AudioTagData
    {
        $comments = [];
        $fields = [
            'TITLE' => $request->title,
            'ALBUMARTIST' => $request->albumArtist,
            'ARTIST' => $request->artist,
            'ALBUM' => $request->album,
            'DATE' => $request->year,
            'TRACKNUMBER' => $request->trackNumber,
            'URL_USER' => $request->urlUser,
            'GENRE' => $request->genre,
            'COMMENT' => $request->comment,
            'COMPOSER' => $request->composer,
            'DISCNUMBER' => $request->discNumber,
            'COPYRIGHT' => $request->copyright,
            'LYRICS' => $request->lyrics,
            'BPM' => $request->bpm,
            'ISRC' => $request->isrc,
        ];

        foreach ($fields as $name => $value) {
            $value = \trim((string) $value);

            if ('' !== $value) {
                $comments[$name] = [$value];
            }
        }

        if (!$request->removeOtherTags) {
            foreach ($this->otherTagData($info) as $name => $values) {
                if ('text' === $name) {
                    continue;
                }

                $items = [];

                foreach ($values as $value) {
                    if (\is_scalar($value) && '' !== \trim((string) $value)) {
                        $items[] = (string) $value;
                    }
                }

                if ([] !== $items) {
                    $comments[\strtoupper($name)] = $items;
                }
            }
        }

        $picture = $this->resolvePicture($info, $request);

        return new AudioTagData($comments, $request->removePicture, $picture['data'] ?? null, $picture['mime'] ?? null);
    }

    /**
     * @param array<string, mixed> $info
     */
    private function setPicture(\getid3_writetags $writer, array $info, AudioTagsUpdateRequest $request): void
    {
        $picture = $this->resolvePicture($info, $request);

        if (null !== $picture) {
            $this->addPicture($writer, $picture['data'], $picture['mime']);
        }
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array{data: string, mime: string}|null
     */
    private function resolvePicture(array $info, AudioTagsUpdateRequest $request): ?array
    {
        if ($request->removePicture) {
            return null;
        }

        if (null !== $request->picture) {
            $mime = $request->picture->getMimeType() ?: $request->picture->getClientMimeType();
            $this->assertPictureMime($mime);

            return ['data' => (string) $request->picture->getContent(), 'mime' => (string) $mime];
        }

        if (null !== $request->pictureUrl && '' !== $request->pictureUrl) {
            $file = $this->remoteFileFetcher->fetch($request->pictureUrl, self::MAX_PICTURE_SIZE);

            try {
                $mime = $file->getMimeType();
                $data = \file_get_contents($file->getPathname());

                if (false === $data) {
                    throw new AudioTagsException('Не удалось прочитать обложку по ссылке.');
                }

                $this->assertPictureMime($mime);

                return ['data' => $data, 'mime' => (string) $mime];
            } finally {
                @\unlink($file->getPathname());
            }
        }

        $existing = $info['comments']['picture'][0] ?? null;

        return \is_array($existing) && isset($existing['data'], $existing['image_mime'])
            ? ['data' => (string) $existing['data'], 'mime' => (string) $existing['image_mime']]
            : null;
    }

    private function addPicture(\getid3_writetags $writer, string $data, string $mime): void
    {
        $writer->tag_data['attached_picture'][0]['data'] = $data;
        $writer->tag_data['attached_picture'][0]['picturetypeid'] = 0;
        $writer->tag_data['attached_picture'][0]['description'] = 'image';
        $writer->tag_data['attached_picture'][0]['mime'] = $mime;
    }

    private function assertPictureMime(?string $mime): void
    {
        if (!\in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new AudioTagsException('Обложка должна быть JPEG, PNG, WebP или GIF.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analyze(string $path): array
    {
        try {
            return $this->getid3->analyze($path);
        } catch (\Throwable $exception) {
            throw new AudioTagsException('Не удалось прочитать аудиофайл.', 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<string, list<string>>
     */
    private function advancedTagData(array $info, AudioTagsUpdateRequest $request): array
    {
        $fileFormat = $info['fileformat'] ?? null;

        return match ($fileFormat) {
            'mp3', 'mp2', 'mp1', 'riff' => [
                'composer' => [$request->composer ?? ''],
                'part_of_a_set' => [$request->discNumber ?? ''],
                'copyright_message' => [$request->copyright ?? ''],
                'unsynchronised_lyric' => [$request->lyrics ?? ''],
                'bpm' => [$request->bpm ?? ''],
                'isrc' => [$request->isrc ?? ''],
            ],
            'flac', 'ogg' => [
                'composer' => [$request->composer ?? ''],
                'discnumber' => [$request->discNumber ?? ''],
                'copyright' => [$request->copyright ?? ''],
                'lyrics' => [$request->lyrics ?? ''],
                'bpm' => [$request->bpm ?? ''],
                'isrc' => [$request->isrc ?? ''],
            ],
            'mpc' => [
                'composer' => [$request->composer ?? ''],
                'disc' => [$request->discNumber ?? ''],
                'copyright' => [$request->copyright ?? ''],
                'lyrics' => [$request->lyrics ?? ''],
                'bpm' => [$request->bpm ?? ''],
                'isrc' => [$request->isrc ?? ''],
            ],
            'real' => [
                'copyright' => [$request->copyright ?? ''],
            ],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $comments
     *
     * @return list<AudioTagsOtherTagResponse>
     */
    private function otherTags(array $comments): array
    {
        $other = [];

        foreach ($comments as $name => $values) {
            if (\in_array($name, self::KNOWN_COMMENT_KEYS, true) || !\is_array($values)) {
                continue;
            }

            $items = [];

            foreach ($values as $value) {
                if (\is_scalar($value) && '' !== \trim((string) $value)) {
                    $items[] = \trim((string) $value);
                }
            }

            if ([] === $items) {
                continue;
            }

            $items = \array_values(\array_unique($items));
            $other[] = new AudioTagsOtherTagResponse('text' === $name ? 'TXXX' : $name, \mb_substr(\implode('; ', $items), 0, 300));
        }

        \usort($other, static fn (AudioTagsOtherTagResponse $a, AudioTagsOtherTagResponse $b): int => \strcmp($a->name, $b->name));

        return $other;
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<string, list<string>|list<array{description: string, data: string, encodingid: int}>>
     */
    private function otherTagData(array $info): array
    {
        $comments = \is_array($info['comments'] ?? null) ? $info['comments'] : [];
        $data = [];

        foreach ($comments as $name => $values) {
            if (\in_array($name, self::KNOWN_COMMENT_KEYS, true) || 'text' === $name || !\is_array($values)) {
                continue;
            }

            $items = [];

            foreach ($values as $value) {
                if (\is_scalar($value) && '' !== \trim((string) $value)) {
                    $items[] = (string) $value;
                }
            }

            if ([] !== $items) {
                $data[$name] = \array_values(\array_unique($items));
            }
        }

        $txxx = [];
        $frames = $info['id3v2']['TXXX'] ?? [];

        foreach (\is_array($frames) ? $frames : [] as $frame) {
            if (!\is_array($frame) || !isset($frame['description'], $frame['data']) || !\is_string($frame['data'])) {
                continue;
            }

            $value = $frame['data'];
            $encoding = $frame['encoding'] ?? null;

            if (\is_string($encoding) && '' !== $encoding && 'UTF-8' !== $encoding) {
                $value = \getid3_lib::iconv_fallback($encoding, 'UTF-8', $value);
            }

            $txxx[] = [
                'description' => (string) $frame['description'],
                'data' => $value,
                'encodingid' => 3,
            ];
        }

        if ([] !== $txxx) {
            $data['text'] = $txxx;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $info
     */
    private function format(array $info): string
    {
        $container = (string) ($info['fileformat'] ?? '');
        $codec = (string) ($info['audio']['dataformat'] ?? '');

        if ('' === $container) {
            return $codec;
        }

        return '' !== $codec && $codec !== $container ? $container.' ('.$codec.')' : $container;
    }

    /**
     * @param array<string, mixed> $info
     */
    private function readOnlyReason(array $info): string
    {
        $container = (string) ($info['fileformat'] ?? '');

        if ('asf' === $container) {
            return 'Формат WMA/ASF доступен только для чтения.';
        }

        return '' !== $container ? 'Формат '.$container.' не поддерживает запись тегов.' : 'Этот формат не поддерживает запись тегов.';
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return list<string>
     */
    private function allowedTagFormats(array $info): array
    {
        $fileFormat = $info['fileformat'] ?? null;
        $audio = \is_array($info['audio'] ?? null) ? $info['audio'] : [];
        $dataFormat = $audio['dataformat'] ?? null;

        return match ($fileFormat) {
            'mp3', 'mp2', 'mp1', 'riff' => ['id3v1', 'id3v2.4'],
            'mpc' => ['ape'],
            'flac' => ['vorbiscomment'],
            'wav' => ['id3v2.4'],
            'real' => ['real'],
            'ogg' => 'vorbis' === $dataFormat ? ['vorbiscomment'] : [],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $comments
     */
    private static function tag(array $comments, string $key): string
    {
        $value = $comments[$key][0] ?? null;

        return \is_string($value) ? $value : '';
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }
}
