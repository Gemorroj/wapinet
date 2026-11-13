<?php

namespace App\Service\Api\File;

use App\Dto\Api\File\FileContent;
use App\Dto\Api\File\FileLinks;
use App\Dto\Api\File\FileListItem;
use App\Dto\Api\File\FileNavigation;
use App\Dto\Api\File\FilePermission;
use App\Dto\Api\File\FileResponse;
use App\Dto\Api\File\PlaylistEntry;
use App\Dto\Api\File\TorrentEntry;
use App\Dto\Api\User\UserSummary;
use App\Entity\File;
use App\Entity\FileTags;
use App\Enum\FileCategory;
use App\Repository\FileRepository;
use App\Security\FileVoter;
use App\Service\Bbcode;
use App\Service\FileContent\Playlist;
use App\Service\FileContent\PreviewGenerator;
use App\Service\FileContent\Torrent;
use App\Service\FileSystem\Storage;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final readonly class FilePresenter
{
    public function __construct(
        private UploaderHelper $uploaderHelper,
        private CacheManager $cacheManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private FileRepository $fileRepository,
        private PreviewGenerator $previewGenerator,
        private Storage $storage,
        private Playlist $playlist,
        private Torrent $torrent,
        private Bbcode $bbcode,
    ) {
    }

    public function toListItem(File $file): FileListItem
    {
        $url = $this->url($file);

        return new FileListItem(
            (int) $file->getId(),
            $file->getOriginalFileName(),
            $file->getDescription(),
            $file->getFileSize(),
            $file->getMimeType(),
            $this->category($file),
            $this->toImmutable($file->getCreatedAt()),
            $this->user($file),
            null !== $file->getPassword(),
            $file->isHidden(),
            $url,
            $this->thumbnail($file, $url),
            $file->getMeta()?->jsonSerialize(),
        );
    }

    public function toResponse(File $file): FileResponse
    {
        $url = $this->url($file);
        $conversion = $this->conversion($file, $url);
        $preview = $this->preview($file, $url);
        $prevId = null;
        $nextId = null;

        if ($file->isImage()) {
            $prevId = $this->fileRepository->getPrevFile((int) $file->getId(), 'image')?->getId();
            $nextId = $this->fileRepository->getNextFile((int) $file->getId(), 'image')?->getId();
        }

        return new FileResponse(
            (int) $file->getId(),
            $file->getOriginalFileName(),
            $file->getDescription(),
            $this->bbcode->render($file->getDescription()),
            $file->getFileSize(),
            $file->getMimeType(),
            $this->category($file),
            $this->toImmutable($file->getCreatedAt()),
            null !== $file->getUpdatedAt() ? $this->toImmutable($file->getUpdatedAt()) : null,
            null !== $file->getLastViewAt() ? $this->toImmutable($file->getLastViewAt()) : null,
            $file->getCountViews(),
            $this->user($file),
            null !== $file->getPassword(),
            $file->isHidden(),
            $file->getHash(),
            $this->tags($file),
            $file->getMeta()?->jsonSerialize(),
            $this->authorizationChecker->isGranted(FileVoter::EDIT, $file),
            $this->authorizationChecker->isGranted(FileVoter::DELETE, $file),
            new FileLinks(
                $url,
                '/api/files/'.$file->getId().'/download',
                $this->browserPath($preview, 'thumbnail'),
                $conversion['url'] ?? null,
                $conversion['format'] ?? null,
            ),
            new FileNavigation($prevId, $nextId),
            new FileContent(
                $file->isPlayableAudio(),
                // A format that needs conversion is playable only when the background conversion is done.
                $file->isPlayableVideo() && (null === $file->getConversion() || null !== $conversion),
                null !== $file->getConversion() && null === $conversion,
                $this->playlist($file),
                $this->torrent($file),
                $this->permissions($file),
            ),
        );
    }

    /**
     * The conversion itself runs in the background, the presenter only reports the result.
     *
     * @return array{url: string, format: string}|null
     */
    private function conversion(File $file, string $url): ?array
    {
        $conversion = $file->getConversion();

        if (null === $conversion || '' === $url) {
            return null;
        }

        $converted = $url.'.'.$conversion->value;

        return $this->storage->has($converted)
            ? ['url' => $converted, 'format' => $conversion->value]
            : null;
    }

    /**
     * @return list<FilePermission>
     */
    private function permissions(File $file): array
    {
        $permissions = $file->getMeta()?->get('permissions');

        if (!\is_array($permissions)) {
            return [];
        }

        $result = [];

        foreach ($permissions as $name => $permission) {
            if (!\is_array($permission)) {
                continue;
            }

            // apk-parser mixes internal markers like API_V23 into the permission list.
            if (\str_starts_with((string) $name, 'API_')) {
                continue;
            }

            $description = isset($permission['description']) && \is_string($permission['description']) ? $permission['description'] : '';
            $flags = \is_array($permission['flags'] ?? null) ? $permission['flags'] : [];

            $result[] = new FilePermission(
                (string) $name,
                $description,
                (bool) ($flags['danger'] ?? false),
                (bool) ($flags['warning'] ?? false),
                (bool) ($flags['cost'] ?? false),
            );
        }

        // Dangerous and paid permissions must be noticed first, the rest keep a predictable order.
        \usort($result, static fn (FilePermission $a, FilePermission $b): int => [
            $b->danger,
            $b->warning,
            $b->cost,
            $a->name,
        ] <=> [
            $a->danger,
            $a->warning,
            $a->cost,
            $b->name,
        ]);

        return $result;
    }

    /**
     * @return list<PlaylistEntry>
     */
    private function playlist(File $file): array
    {
        $source = $file->getFile();

        if (!$file->isPlaylist() || null === $source) {
            return [];
        }

        try {
            $data = $this->playlist->parseFile($source);
        } catch (\Throwable) {
            return [];
        }

        $tracks = [];

        foreach ($data as $entry) {
            $path = $entry->getPath();
            $title = $path;

            foreach ($entry->getExtTags() as $tag) {
                if (\method_exists($tag, 'getTitle')) {
                    $tagTitle = (string) $tag->getTitle();

                    if ('' !== $tagTitle) {
                        $title = $tagTitle;
                    }

                    break;
                }
            }

            $tracks[] = new PlaylistEntry($title, $path);
        }

        return $tracks;
    }

    /**
     * @return list<TorrentEntry>
     */
    private function torrent(File $file): array
    {
        $source = $file->getFile();

        if (!$file->isTorrent() || null === $source) {
            return [];
        }

        try {
            $data = $this->torrent->decodeFile($source);
        } catch (\Throwable) {
            return [];
        }

        $entries = [];

        if (isset($data['info']['files'])) {
            foreach ($data['info']['files'] as $item) {
                $entries[] = new TorrentEntry(\implode('/', $item['path']), (int) $item['length']);
            }
        } elseif (isset($data['info']['name'], $data['info']['length'])) {
            $entries[] = new TorrentEntry((string) $data['info']['name'], (int) $data['info']['length']);
        }

        return $entries;
    }

    private function url(File $file): string
    {
        return $this->uploaderHelper->asset($file, 'file') ?? '';
    }

    private function thumbnail(File $file, string $url): ?string
    {
        return $this->browserPath($this->preview($file, $url), 'thumbnail');
    }

    /**
     * Picture that represents the file: the file itself for images, an extracted
     * frame for video, an embedded cover for audio, an icon for applications.
     */
    private function preview(File $file, string $url): ?string
    {
        return $this->previewGenerator->source($file, $url);
    }

    private function browserPath(?string $source, string $filter): ?string
    {
        if (null === $source) {
            return null;
        }

        try {
            return $this->cacheManager->getBrowserPath(
                $source,
                $filter,
                referenceType: UrlGeneratorInterface::ABSOLUTE_PATH,
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function user(File $file): ?UserSummary
    {
        $user = $file->getUser();

        return null !== $user ? UserSummary::fromUser($user) : null;
    }

    /**
     * @return string[]
     */
    private function tags(File $file): array
    {
        $names = [];

        /** @var FileTags $fileTag */
        foreach ($file->getFileTags() as $fileTag) {
            $name = $fileTag->getTag()?->getName();
            if (null !== $name && '' !== $name) {
                $names[] = $name;
            }
        }

        \sort($names);

        return $names;
    }

    private function category(File $file): string
    {
        return FileCategory::fromFile($file)->value;
    }

    private function toImmutable(\DateTimeInterface $date): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($date);
    }
}
