<?php

namespace App\Entity;

use App\Entity\File\Meta;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use const PATHINFO_FILENAME;
use Serializable;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 */
class File implements Serializable
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var User|null
     */
    protected $user;
    /**
     * @var string
     */
    protected $ip;
    /**
     * @var string
     */
    protected $browser;
    /**
     * @var DateTime
     */
    protected $createdAt;
    /**
     * @var DateTime|null
     */
    protected $updatedAt;
    /**
     * @var DateTime|null
     */
    protected $lastViewAt;
    /**
     * @var int
     */
    protected $countViews = 0;
    /**
     * @var string|null
     */
    protected $salt;
    /**
     * @var string|null
     */
    protected $password;
    /**
     * @var string|null
     */
    protected $plainPassword;
    /**
     * @var string
     */
    protected $mimeType;
    /**
     * @var int
     */
    protected $fileSize;
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var BaseFile
     * @Assert\File
     * @Vich\UploadableField(mapping="file", fileNameProperty="fileName", size="fileSize")
     */
    protected $file;
    /**
     * @var string
     */
    protected $originalFileName;
    /**
     * @var string
     * @Assert\Length(max=5000, allowEmptyString="false")
     */
    protected $description;
    /**
     * @var string
     */
    protected $hash;
    /**
     * @var Meta|null
     */
    protected $meta;
    /**
     * @var Collection
     */
    protected $fileTags;
    /**
     * @var Collection
     */
    protected $tags;
    /**
     * @var bool
     */
    protected $hidden = true;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->fileTags = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function serialize(): string
    {
        $vars = \get_object_vars($this);
        $vars['file'] = $this->getFile()->getPathname();

        return \serialize($vars);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        $vars = \unserialize($serialized, ['allowed_classes' => true]); // true для полного соответствия с поведением doctrine
        $this->file = new BaseFile($vars['file'], false); //fix события (файлы могут быть удалены)
        unset($vars['file']);

        foreach ($vars as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     *
     * @return File
     */
    public function setHidden($hidden): self
    {
        $this->hidden = (bool) $hidden;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return File
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get meta.
     *
     * @return Meta|null
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Set meta.
     *
     * @param Meta $meta
     *
     * @return File
     */
    public function setMeta(Meta $meta = null): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get fileTags.
     *
     * @return Collection
     */
    public function getFileTags()
    {
        return $this->fileTags ?: new ArrayCollection();
    }

    /**
     * Set fileTags.
     *
     * @return File
     */
    public function setFileTags(Collection $fileTags): self
    {
        $this->fileTags = $fileTags;

        return $this;
    }

    /**
     * Get Tags
     * Использовать только для отображения. В БД этого поля нет.
     *
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags ?: new ArrayCollection();
    }

    /**
     * Set Tags
     * Использовать только для отображения. В БД этого поля нет.
     *
     * @param Collection $tags
     *
     * @return File
     */
    public function setTags(Collection $tags = null): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return File
     */
    public function setUser(User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return File
     */
    public function setIp($ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param string $browser
     *
     * @return File
     */
    public function setBrowser($browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return File
     */
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new DateTime();

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return File
     */
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastViewAt()
    {
        return $this->lastViewAt;
    }

    /**
     * @return File
     */
    public function setLastViewAt(DateTime $lastViewAt): self
    {
        $this->lastViewAt = $lastViewAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getCountViews()
    {
        return $this->countViews;
    }

    /**
     * @param int $countViews
     *
     * @return File
     */
    public function setCountViews($countViews): self
    {
        $this->countViews = $countViews;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return File
     */
    public function removeSalt(): self
    {
        $this->salt = null;

        return $this;
    }

    /**
     * @return File
     */
    public function setSaltValue(): self
    {
        $this->salt = \base_convert(\sha1(\uniqid(\mt_rand(), true)), 16, 36);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     *
     * @return File
     */
    public function setPlainPassword($plainPassword = null): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     *
     * @return File
     */
    public function setPassword($password = null): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     *
     * @return File
     */
    public function setMimeType($mimeType): self
    {
        // по mime определять принадлежность к категории
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     *
     * @return File
     */
    public function setFileSize($fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @return File
     */
    public function setFileName($fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }

    /**
     * @param string $originalFileName
     *
     * @return File
     */
    public function setOriginalFileName($originalFileName): self
    {
        $this->originalFileName = $originalFileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalFileNameWithoutExtension()
    {
        return \pathinfo($this->getOriginalFileName(), PATHINFO_FILENAME);
    }

    /**
     * @return BaseFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param BaseFile $file
     *
     * @return File
     */
    public function setFile(BaseFile $file = null): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return File
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * MD5 file hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * MD5 file hash.
     *
     * @param string $hash
     *
     * @return File
     */
    public function setHash($hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        return 0 === \mb_strpos($this->getMimeType(), 'image/') || 'application/postscript' === $this->getMimeType() || 'application/illustrator' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isVideo()
    {
        return 0 === \mb_strpos($this->getMimeType(), 'video/') || 'application/vnd.rn-realmedia' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isAudio()
    {
        return 0 === \mb_strpos($this->getMimeType(), 'audio/') && 'audio/x-mpegurl' !== $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isText()
    {
        return 0 === \mb_strpos($this->getMimeType(), 'text/');
    }

    /**
     * @return bool
     */
    public function isXml()
    {
        return 'application/xml' === $this->getMimeType() || false !== \mb_strpos($this->getMimeType(), '+xml');
    }

    /**
     * @return bool
     */
    public function isArchive()
    {
        return \in_array($this->getMimeType(), [
            'application/zip', // zip
            'application/x-rar-compressed', // rar
            'application/x-bzip', // bz
            'application/x-bzip2', // bz2
            'application/x-7z-compressed', // 7z
            'application/x-tar', // tar
            'application/vnd.ms-cab-compressed', // cab
            'application/x-iso9660-image', // iso
            'application/x-gzip', // gz
            'application/gzip', // gz
            'application/x-ace-compressed', // ace
            'application/x-lzh-compressed', // lzh
        ], true);
    }

    /**
     * @return bool
     */
    public function isFlash()
    {
        return 'application/x-shockwave-flash' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isJavaApp()
    {
        return 'application/java-archive' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isAndroidApp()
    {
        return 'application/vnd.android.package-archive' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isSymbianApp()
    {
        return 'application/vnd.symbian.install' === $this->getMimeType() || 'x-epoc/x-sisx-app' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isWindowsApp()
    {
        return 'application/x-msdownload' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isPdf()
    {
        return 'application/pdf' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isWord()
    {
        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' === $this->getMimeType() || 'application/msword' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isRtf()
    {
        return 'application/rtf' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isExcel()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' === $this->getMimeType() || 'application/vnd.ms-excel' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function is3gp()
    {
        return 'video/3gpp' === $this->getMimeType() || 'video/3gpp2' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isAvi()
    {
        return 'video/x-msvideo' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isWmv()
    {
        return 'video/x-ms-wmv' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isMp3()
    {
        return 'audio/mpeg' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isM4a()
    {
        return 'audio/mp4' === $this->getMimeType() || 'audio/x-m4a' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isM4v()
    {
        return 'video/mp4' === $this->getMimeType() || 'video/x-m4v' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isOga()
    {
        return 'audio/ogg' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isOgv()
    {
        return 'video/ogg' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isWebma()
    {
        return 'audio/webm' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isWebmv()
    {
        return 'video/webm' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isWav()
    {
        return 'audio/x-wav' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isAmr()
    {
        return 'audio/3gpp' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isFla()
    {
        return 'audio/x-flv' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isFlv()
    {
        return 'video/x-flv' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isFlac()
    {
        return 'audio/x-flac' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isTorrent()
    {
        return 'application/x-bittorrent' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isPlaylist()
    {
        return 'audio/x-mpegurl' === $this->getMimeType();
    }

    /**
     * @return bool
     */
    public function isPlayableVideo()
    {
        return $this->isVideo() && (
            $this->isM4v() ||
            $this->isOgv() ||
            $this->isWebmv() ||
            $this->isFlv() ||
            $this->is3gp() ||
            $this->isAvi() ||
            $this->isWmv()
        );
    }

    /**
     * @return bool
     */
    public function isPlayableAudio()
    {
        return $this->isAudio() && (
            $this->isMp3() ||
            $this->isM4a() ||
            $this->isOga() ||
            $this->isWebma() ||
            $this->isWav() ||
            $this->isFla() ||
            $this->isFlac() ||
            $this->isAmr()
        );
    }

    /**
     * @return bool
     */
    public function isExtractableArchive()
    {
        return $this->isArchive();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getOriginalFileName();
    }
}
