<?php

declare(strict_types=1);

namespace App\Enum;

use App\Entity\File;

enum FileCategory: string
{
    case Video = 'video';
    case Audio = 'audio';
    case Image = 'image';
    case Text = 'text';
    case Office = 'office';
    case Archive = 'archive';
    case Android = 'android';
    case Java = 'java';
    case Windows = 'windows';
    case Playlist = 'playlist';
    case Torrent = 'torrent';
    case Other = 'other';

    public static function fromFile(File $file): self
    {
        return match (true) {
            $file->isAndroidApp() => self::Android,
            $file->isJavaApp() => self::Java,
            $file->isWindowsApp() => self::Windows,
            $file->isImage() => self::Image,
            $file->isVideo() => self::Video,
            $file->isAudio() => self::Audio,
            $file->isPdf(), $file->isWord(), $file->isExcel(), $file->isRtf() => self::Office,
            $file->isText(), $file->isXml(), $file->isJson() => self::Text,
            $file->isPlaylist() => self::Playlist,
            $file->isTorrent() => self::Torrent,
            $file->isArchive() => self::Archive,
            default => self::Other,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return \array_map(static fn (self $category): string => $category->value, self::cases());
    }
}
