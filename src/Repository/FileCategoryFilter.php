<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\FileCategory;
use App\Enum\FileMime;
use Doctrine\ORM\QueryBuilder;

/**
 * Builds the SQL condition for a file category.
 *
 * The condition mirrors {@see FileCategory::fromFile()} so filtering and the
 * category shown in the UI always agree.
 */
final class FileCategoryFilter
{
    public static function apply(QueryBuilder $qb, FileCategory $category): void
    {
        [$dql, $parameters] = self::condition($category);

        $qb->andWhere('('.$dql.')');

        foreach ($parameters as $name => $value) {
            $qb->setParameter($name, $value);
        }
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function condition(FileCategory $category): array
    {
        return match ($category) {
            FileCategory::Image => self::likeAndIn('image', 'image/%', FileMime::IMAGE_EXTRA),
            FileCategory::Video => self::likeAndIn('video', 'video/%', FileMime::VIDEO_EXTRA),
            FileCategory::Audio => self::audio(),
            FileCategory::Office => self::mimeIn('office', [...FileMime::PDF, ...FileMime::WORD, ...FileMime::EXCEL, ...FileMime::RTF]),
            FileCategory::Text => self::text(),
            FileCategory::Playlist => self::mimeIn('playlist', FileMime::PLAYLIST),
            FileCategory::Torrent => self::mimeIn('torrent', FileMime::TORRENT),
            FileCategory::Archive => self::archive(),
            FileCategory::Android => self::android(),
            FileCategory::Java => self::java(),
            FileCategory::Windows => self::mimeIn('windows', FileMime::WINDOWS),
            FileCategory::Other => self::other(),
        };
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function audio(): array
    {
        [$playlistDql, $playlistParameters] = self::mimeIn('audio_playlist', FileMime::PLAYLIST);

        return [
            '(f.mimeType LIKE :audio_like AND NOT ('.$playlistDql.'))',
            ['audio_like' => 'audio/%'] + $playlistParameters,
        ];
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function text(): array
    {
        return [
            '(f.mimeType LIKE :text_like OR f.mimeType = :text_xml OR f.mimeType LIKE :text_plus_xml OR f.mimeType = :text_json) AND f.mimeType <> :text_rtf AND f.mimeType NOT LIKE :text_image',
            [
                'text_like' => 'text/%',
                'text_xml' => 'application/xml',
                'text_plus_xml' => '%+xml',
                'text_json' => 'application/json',
                'text_rtf' => 'text/rtf',
                'text_image' => 'image/%',
            ],
        ];
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function archive(): array
    {
        [$dql, $parameters] = self::mimeIn('archive', FileMime::ARCHIVE);

        return ['('.$dql.') AND f.fileName NOT LIKE :archive_apk', $parameters + ['archive_apk' => '%.apk']];
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function android(): array
    {
        [$javaDql, $javaParameters] = self::mimeIn('android_java', [...FileMime::JAVA, ...FileMime::ZIP]);

        return [
            '(f.mimeType = :android_mime OR ('.$javaDql.') AND f.fileName LIKE :android_apk)',
            ['android_mime' => FileMime::ANDROID[0], 'android_apk' => '%.apk'] + $javaParameters,
        ];
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function java(): array
    {
        [$dql, $parameters] = self::mimeIn('java', FileMime::JAVA);

        return ['('.$dql.') AND f.fileName NOT LIKE :java_apk', $parameters + ['java_apk' => '%.apk']];
    }

    /**
     * @return array{string, array<string, string>}
     */
    private static function other(): array
    {
        $conditions = [];
        $parameters = [];

        foreach (FileCategory::cases() as $category) {
            if (FileCategory::Other === $category) {
                continue;
            }

            [$dql, $categoryParameters] = self::condition($category);
            $conditions[] = '('.$dql.')';
            $parameters += $categoryParameters;
        }

        return ['NOT ('.\implode(' OR ', $conditions).')', $parameters];
    }

    /**
     * @param list<string> $mimeTypes
     *
     * @return array{string, array<string, string>}
     */
    private static function likeAndIn(string $prefix, string $like, array $mimeTypes): array
    {
        [$inDql, $parameters] = self::mimeIn($prefix.'_exact', $mimeTypes);

        return [
            'f.mimeType LIKE :'.$prefix.'_like OR '.$inDql,
            [$prefix.'_like' => $like] + $parameters,
        ];
    }

    /**
     * @param list<string> $mimeTypes
     *
     * @return array{string, array<string, string>}
     */
    private static function mimeIn(string $prefix, array $mimeTypes): array
    {
        $placeholders = [];
        $parameters = [];

        foreach ($mimeTypes as $index => $mimeType) {
            $name = \sprintf('%s_%d', $prefix, $index + 1);
            $placeholders[] = ':'.$name;
            $parameters[$name] = $mimeType;
        }

        return ['f.mimeType IN('.\implode(', ', $placeholders).')', $parameters];
    }
}
