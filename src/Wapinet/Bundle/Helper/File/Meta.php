<?php
namespace Wapinet\Bundle\Helper\File;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Entity\File as EntityFile;
use Wapinet\Bundle\Entity\File\Meta as FileMeta;


/**
 * Meta хэлпер
 */
class Meta
{
    /**
     * @var FileMeta
     */
    protected $fileMeta;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityFile|null
     */
    protected $file;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->fileMeta = new FileMeta();
    }

    /**
     * @param EntityFile $file
     * @return Meta
     */
    public function setFile(EntityFile $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Получаем мета-информацию файла
     *
     * @throws \RuntimeException
     * @return FileMeta
     */
    public function getFileMeta()
    {
        if (null === $this->file) {
            throw new \RuntimeException('Не указан файл, мета-информацию которого нужно получить.');
        }

        if ($this->file->isAndroidApp()) {
            $this->setAndroidMeta();
        } elseif ($this->file->isAudio()) {
            $this->setAudioMeta();
        } elseif ($this->file->isVideo()) {
            $this->setVideoMeta();
        } elseif ($this->file->isImage()) {
            $this->setImageMeta();
        } elseif ($this->file->isTorrent()) {
            $this->setTorrentMeta();
        }

        return $this->fileMeta;
    }


    /**
     * @return Meta
     */
    protected function setAndroidMeta()
    {
        $apk = $this->container->get('apk');
        $apk->init($this->file->getFile()->getPathname());

        $manifest = $apk->getManifest();

        $this->fileMeta->set('versionName', $manifest->getVersionName());
        $this->fileMeta->set('packageName', $manifest->getPackageName());
        if ($manifest->getMinSdkLevel()) {
            $this->fileMeta->set('minSdkVersions', $manifest->getMinSdk()->versions);
        }

        $permissions = $manifest->getPermissions();
        if ($permissions) {
            $this->fileMeta->set('permissions', $permissions);
        }

        return $this;
    }

    /**
     * @return Meta
     */
    protected function setAudioMeta()
    {
        $ffprobe = $this->container->get('dubture_ffmpeg.ffprobe');
        $info = $ffprobe->streams($this->file->getFile()->getPathname())->audios()->first();

        if (null !== $info) {
            if ($info->has('duration')) {
                $this->fileMeta->set('duration', $info->get('duration'));
            }
            if ($info->has('codec_name')) {
                $this->fileMeta->set('codecName', $info->get('codec_name'));
            }
            if ($info->has('bit_rate')) {
                $this->fileMeta->set('bitRate', $info->get('bit_rate'));
            }
            if ($info->has('sample_rate')) {
                $this->fileMeta->set('sampleRate', $info->get('sample_rate'));
            }
        }

        return $this;
    }


    /**
     * @return Meta
     */
    protected function setVideoMeta()
    {
        $ffprobe = $this->container->get('dubture_ffmpeg.ffprobe');
        $streams = $ffprobe->streams($this->file->getFile()->getPathname());
        $videoInfo = $streams->videos()->first();
        $audioInfo = $streams->audios()->first();

        if (null !== $videoInfo) {
            $this->fileMeta->set('width', $videoInfo->getDimensions()->getWidth());
            $this->fileMeta->set('height', $videoInfo->getDimensions()->getHeight());

            if ($videoInfo->has('duration')) {
                $this->fileMeta->set('duration', $videoInfo->get('duration'));
            }
            if ($videoInfo->has('codec_name')) {
                $this->fileMeta->set('codecName', $videoInfo->get('codec_name'));
            }
            if ($videoInfo->has('bit_rate')) {
                $this->fileMeta->set('bitRate', $videoInfo->get('bit_rate'));
            }
        }

        if (null !== $audioInfo) {
            if ($audioInfo->has('codec_name')) {
                $this->fileMeta->set('audioCodecName', $audioInfo->get('codec_name'));
            }
            if ($audioInfo->has('bit_rate')) {
                $this->fileMeta->set('audioBitRate', $audioInfo->get('bit_rate'));
            }
            if ($audioInfo->has('sample_rate')) {
                $this->fileMeta->set('audioSampleRate', $audioInfo->get('sample_rate'));
            }
        }

        return $this;
    }


    /**
     * @return Meta
     */
    protected function setImageMeta()
    {
        $imagine = $this->container->get('liip_imagine');
        $info = $imagine->open($this->file->getFile()->getPathname());

        $this->fileMeta->set('width', $info->getSize()->getWidth());
        $this->fileMeta->set('height', $info->getSize()->getHeight());

        $infoMetadata = $info->metadata();

        if ($infoMetadata->offsetExists('exif.DateTimeOriginal')) {
            $this->fileMeta->set('dateTimeOriginal', $infoMetadata->offsetGet('exif.DateTimeOriginal'));
        } elseif ($infoMetadata->offsetExists('ifd0.DateTimeOriginal')) {
            $this->fileMeta->set('dateTimeOriginal', $infoMetadata->offsetGet('ifd0.DateTimeOriginal'));
        }

        if ($infoMetadata->offsetExists('exif.DateTime')) {
            $this->fileMeta->set('dateTime', $infoMetadata->offsetGet('exif.DateTime'));
        } elseif ($infoMetadata->offsetExists('ifd0.DateTime')) {
            $this->fileMeta->set('dateTime', $infoMetadata->offsetGet('ifd0.DateTime'));
        }

        if ($infoMetadata->offsetExists('exif.Make')) {
            $this->fileMeta->set('make', $infoMetadata->offsetGet('exif.Make'));
        } elseif ($infoMetadata->offsetExists('ifd0.Make')) {
            $this->fileMeta->set('make', $infoMetadata->offsetGet('ifd0.Make'));
        }

        if ($infoMetadata->offsetExists('exif.Model')) {
            $this->fileMeta->set('model', $infoMetadata->offsetGet('exif.Model'));
        } elseif ($infoMetadata->offsetExists('ifd0.Model')) {
            $this->fileMeta->set('model', $infoMetadata->offsetGet('ifd0.Model'));
        }

        if ($infoMetadata->offsetExists('exif.Software')) {
            $this->fileMeta->set('software', $infoMetadata->offsetGet('exif.Software'));
        } elseif ($infoMetadata->offsetExists('ifd0.Software')) {
            $this->fileMeta->set('software', $infoMetadata->offsetGet('ifd0.Software'));
        }

        if ($infoMetadata->offsetExists('exif.COMMENT')) {
            $commentTrimed = \trim($infoMetadata->offsetGet('exif.COMMENT'));
            if ('' !== $commentTrimed) {
                $this->fileMeta->set('comment', $infoMetadata->offsetGet('exif.COMMENT'));
            }
        } elseif ($infoMetadata->offsetExists('exif.UserComment')) {
            $commentTrimed = \trim($infoMetadata->offsetGet('exif.UserComment'));
            if ('' !== $commentTrimed) {
                $this->fileMeta->set('comment', $infoMetadata->offsetGet('exif.UserComment'));
            }
        }

        return $this;
    }


    /**
     * @return Meta
     */
    protected function setTorrentMeta()
    {
        $torrent = $this->container->get('torrent');

        $data = $torrent->decodeFile($this->file->getFile());


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
            $this->fileMeta->set('size', $size);
        }
        if (isset($data['info']['name'])) {
            $this->fileMeta->set('name', $data['info']['name']);
        }
        if (isset($data['creation date'])) {
            $this->fileMeta->set('datetime', new \DateTime('@' . $data['creation date']));
        }
        if (isset($data['comment'])) {
            $this->fileMeta->set('comment', $data['comment']);
        }

        return $this;
    }
}
