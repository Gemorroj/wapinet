<?php

declare(strict_types=1);

namespace App\Enum;

final class FileMime
{
    public const array PLAYLIST = [
        'audio/x-mpegurl',
        'application/m3u',
        'application/vnd.apple.mpegurl',
        'audio/m3u',
        'audio/mpegurl',
        'audio/x-m3u',
        'audio/x-mp3-playlist',
        'audio/x-scpls',
        'application/pls+xml',
        'audio/x-ms-asx',
        'application/xspf+xml',
    ];

    public const array TORRENT = ['application/x-bittorrent'];

    public const array ZIP = [
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
    ];

    public const array JAVA = [
        'application/java-archive',
        'application/x-java-archive',
        'application/x-jar',
    ];

    public const array ANDROID = ['application/vnd.android.package-archive'];

    public const array WINDOWS = [
        'application/vnd.microsoft.portable-executable',
        'application/x-msdownload',
        'application/x-ms-dos-executable',
        'application/x-dosexec',
        'application/x-msi',
        'application/x-ms-installer',
        'application/vnd.ms-msi',
    ];

    public const array IMAGE_EXTRA = [
        'application/postscript',
        'application/illustrator',
        'application/vnd.adobe.illustrator',
    ];

    /**
     * Formats the image driver can resize. Everything else (SVG, TIFF, EPS) is
     * served as is, because a thumbnail request for it fails with a 500 error.
     */
    public const array IMAGE_THUMBNAIL = [
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/apng',
        'image/x-png',
        'image/gif',
        'image/webp',
        'image/avif',
        'image/bmp',
        'image/x-ms-bmp',
    ];

    public const array VIDEO_EXTRA = [
        'application/vnd.rn-realmedia',
        'application/vnd.rn-realmedia-vbr',
        'application/x-shockwave-flash',
    ];

    public const array PDF = [
        'application/pdf',
        'application/acrobat',
        'application/nappdf',
        'application/x-pdf',
        'image/pdf',
    ];

    public const array WORD = [
        'application/msword',
        'application/vnd.ms-word',
        'application/x-msword',
        'zz-application/zz-winassoc-doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'application/vnd.ms-word.document.macroenabled.12',
        'application/vnd.oasis.opendocument.text',
    ];

    public const array EXCEL = [
        'application/vnd.ms-excel',
        'application/msexcel',
        'application/x-msexcel',
        'zz-application/zz-winassoc-xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'application/vnd.ms-excel.sheet.macroenabled.12',
        'application/vnd.oasis.opendocument.spreadsheet',
    ];

    public const array RTF = [
        'application/rtf',
        'text/rtf',
    ];

    public const array ARCHIVE = [
        'application/zip',
        'application/x-zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-rar',
        'application/vnd.rar',
        'application/x-bzip',
        'application/x-bzip2',
        'application/x-bz2',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/vnd.ms-cab-compressed',
        'zz-application/zz-winassoc-cab',
        'application/x-cd-image',
        'application/x-gamecube-iso-image',
        'application/x-gamecube-rom',
        'application/x-iso9660-image',
        'application/x-saturn-rom',
        'application/x-sega-cd-rom',
        'application/x-wbfs',
        'application/x-wia',
        'application/x-wii-iso-image',
        'application/x-wii-rom',
        'application/x-gzip',
        'application/gzip',
        'application/x-ace',
        'application/x-ace-compressed',
        'application/x-lha',
        'application/x-lzh-compressed',
        'application/x-zstd',
        'application/zstd',
        'application/x-xz',
        'application/x-lzma',
        'application/x-lz',
        'application/x-compress',
        'application/x-lzip',
        'application/x-lrzip',
        'application/x-lzop',
        'application/x-arj',
        'application/x-cpio',
        'application/x-rpm',
        'application/vnd.debian.binary-package',
        'application/x-debian-package',
    ];
}
