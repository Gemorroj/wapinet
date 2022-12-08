<?php

namespace App\Service\File;

use App\Entity\File as EntityFile;
use App\Entity\File\Meta as FileMeta;
use App\Service\Apk as ApkHelper;
use App\Service\Ffmpeg as FfmpegHelper;
use App\Service\Midi as MidiHelper;
use App\Service\Torrent as TorrentHelper;
use Imagine\Image\AbstractImagine;

class Meta
{
    public function __construct(
        private FfmpegHelper $ffmpegHelper,
        private ApkHelper $apkHelper,
        private TorrentHelper $torrentHelper,
        private AbstractImagine $imagine,
        private MidiHelper $midiHelper
    ) {
    }

    /**
     * Получаем мета-информацию файла.
     *
     * @throws \RuntimeException
     */
    public function getFileMeta(EntityFile $file): FileMeta
    {
        if ($file->isAndroidApp()) {
            return $this->makeAndroidMeta($file);
        }
        if ($file->isVideo()) {
            return $this->makeVideoMeta($file);
        }
        if ($file->isAudio()) {
            return $this->makeAudioMeta($file);
        }
        if ($file->isImage()) {
            return $this->makeImageMeta($file);
        }
        if ($file->isTorrent()) {
            return $this->makeTorrentMeta($file);
        }

        return new FileMeta();
    }

    private function makeAndroidMeta(EntityFile $file): FileMeta
    {
        $fileMeta = new FileMeta();
        $this->apkHelper->init($file->getFile()->getPathname());

        $manifest = $this->apkHelper->getManifest();

        $fileMeta->set('versionName', $manifest->getVersionName());
        $fileMeta->set('packageName', $manifest->getPackageName());
        if ($manifest->getMinSdkLevel()) {
            $fileMeta->set('minSdkVersions', $manifest->getMinSdk()->versions);
        }

        $permissions = $manifest->getPermissions();
        if ($permissions) {
            $fileMeta->set('permissions', $permissions);
        }

        return $fileMeta;
    }

    private function makeAudioMeta(EntityFile $file): FileMeta
    {
        $fileMeta = new FileMeta();

        if ($file->isMidi()) {
            $duration = $this->midiHelper->getDuration($file->getFile()->getPathname());
            $fileMeta->set('duration', $duration);

            return $fileMeta;
        }

        $ffprobe = $this->ffmpegHelper->getFfprobe();
        $info = $ffprobe->streams($file->getFile()->getPathname())->audios()->first();

        if (null !== $info) {
            if ($info->has('duration')) {
                $fileMeta->set('duration', $info->get('duration'));
            }
            if ($info->has('codec_name')) {
                $fileMeta->set('codecName', $info->get('codec_name'));
            }
            if ($info->has('bit_rate')) {
                $fileMeta->set('bitRate', $info->get('bit_rate'));
            }
            if ($info->has('sample_rate')) {
                $fileMeta->set('sampleRate', $info->get('sample_rate'));
            }
        }

        return $fileMeta;
    }

    private function makeVideoMeta(EntityFile $file): FileMeta
    {
        $fileMeta = new FileMeta();

        $ffprobe = $this->ffmpegHelper->getFfprobe();
        $streams = $ffprobe->streams($file->getFile()->getPathname());
        $videoInfo = $streams->videos()->first();
        $audioInfo = $streams->audios()->first();

        if (null !== $videoInfo) {
            $fileMeta->set('width', $videoInfo->getDimensions()->getWidth());
            $fileMeta->set('height', $videoInfo->getDimensions()->getHeight());

            if ($videoInfo->has('duration')) {
                $fileMeta->set('duration', $videoInfo->get('duration'));
            }
            if ($videoInfo->has('codec_name')) {
                $fileMeta->set('codecName', $videoInfo->get('codec_name'));
            }
            if ($videoInfo->has('bit_rate')) {
                $fileMeta->set('bitRate', $videoInfo->get('bit_rate'));
            }
        }

        if (null !== $audioInfo) {
            if ($audioInfo->has('codec_name')) {
                $fileMeta->set('audioCodecName', $audioInfo->get('codec_name'));
            }
            if ($audioInfo->has('bit_rate')) {
                $fileMeta->set('audioBitRate', $audioInfo->get('bit_rate'));
            }
            if ($audioInfo->has('sample_rate')) {
                $fileMeta->set('audioSampleRate', $audioInfo->get('sample_rate'));
            }
        }

        return $fileMeta;
    }

    private function makeImageMeta(EntityFile $file): FileMeta
    {
        $fileMeta = new FileMeta();
        $info = $this->imagine->open($file->getFile()->getPathname());

        $fileMeta->set('width', $info->getSize()->getWidth());
        $fileMeta->set('height', $info->getSize()->getHeight());

        $infoMetadata = $info->metadata();

        if ($infoMetadata->offsetExists('exif.DateTimeOriginal')) {
            $fileMeta->set('dateTimeOriginal', $infoMetadata->offsetGet('exif.DateTimeOriginal'));
        } elseif ($infoMetadata->offsetExists('ifd0.DateTimeOriginal')) {
            $fileMeta->set('dateTimeOriginal', $infoMetadata->offsetGet('ifd0.DateTimeOriginal'));
        }

        if ($infoMetadata->offsetExists('exif.DateTime')) {
            $fileMeta->set('dateTime', $infoMetadata->offsetGet('exif.DateTime'));
        } elseif ($infoMetadata->offsetExists('ifd0.DateTime')) {
            $fileMeta->set('dateTime', $infoMetadata->offsetGet('ifd0.DateTime'));
        }

        if ($infoMetadata->offsetExists('exif.Make')) {
            $fileMeta->set('make', $infoMetadata->offsetGet('exif.Make'));
        } elseif ($infoMetadata->offsetExists('ifd0.Make')) {
            $fileMeta->set('make', $infoMetadata->offsetGet('ifd0.Make'));
        }

        if ($infoMetadata->offsetExists('exif.Model')) {
            $fileMeta->set('model', $infoMetadata->offsetGet('exif.Model'));
        } elseif ($infoMetadata->offsetExists('ifd0.Model')) {
            $fileMeta->set('model', $infoMetadata->offsetGet('ifd0.Model'));
        }

        if ($infoMetadata->offsetExists('exif.Software')) {
            $fileMeta->set('software', $infoMetadata->offsetGet('exif.Software'));
        } elseif ($infoMetadata->offsetExists('ifd0.Software')) {
            $fileMeta->set('software', $infoMetadata->offsetGet('ifd0.Software'));
        }

        if ($infoMetadata->offsetExists('exif.COMMENT')) {
            $commentTrimmed = \trim($infoMetadata->offsetGet('exif.COMMENT'));
            if ('' !== $commentTrimmed) {
                $fileMeta->set('comment', $infoMetadata->offsetGet('exif.COMMENT'));
            }
        } elseif ($infoMetadata->offsetExists('exif.UserComment')) {
            $commentTrimmed = \trim($infoMetadata->offsetGet('exif.UserComment'));
            if ('' !== $commentTrimmed) {
                $fileMeta->set('comment', $infoMetadata->offsetGet('exif.UserComment'));
            }
        }

        return $fileMeta;
    }

    private function makeTorrentMeta(EntityFile $file): FileMeta
    {
        $fileMeta = new FileMeta();
        $data = $this->torrentHelper->decodeFile($file->getFile());

        if (isset($data['info']['length'])) {
            $size = $data['info']['length'];
        } else {
            $size = 0;
            if (isset($data['info']['files'])) {
                foreach ($data['info']['files'] as $entry) {
                    $size += $entry['length'];
                }
            }
        }

        if ($size) {
            $fileMeta->set('size', $size);
        }
        if (isset($data['info']['name'])) {
            $fileMeta->set('name', $data['info']['name']);
        }
        if (isset($data['creation date'])) {
            $fileMeta->set('datetime', new \DateTime('@'.$data['creation date']));
        }
        if (isset($data['comment'])) {
            $fileMeta->set('comment', $data['comment']);
        }

        return $fileMeta;
    }
}
