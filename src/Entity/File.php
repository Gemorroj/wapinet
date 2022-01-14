<?php

namespace App\Entity;

use App\Entity\File\Meta;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * File.
 *
 * @Vich\Uploadable
 * @ORM\Table(name="file", indexes={@ORM\Index(name="hash_idx", columns={"hash"})})
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 * @ORM\HasLifecycleCallbacks
 */
class File implements PasswordAuthenticatedUserInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", nullable=false)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="browser", type="string", nullable=false)
     */
    private $browser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private ?\DateTime $updatedAt = null;

    /**
     * @ORM\Column(name="last_view_at", type="datetime", nullable=true)
     */
    private ?\DateTime $lastViewAt = null;

    /**
     * @ORM\Column(name="count_views", type="integer", nullable=false)
     */
    private int $countViews = 0;

    /**
     * @ORM\Column(name="password", type="string", nullable=true)
     */
    private ?string $password = null;
    private ?string $plainPassword = null;

    /**
     * @ORM\Column(name="mime_type", type="string", nullable=false)
     */
    private string $mimeType = '';

    /**
     * @ORM\Column(name="file_size", type="integer", nullable=false)
     */
    private int $fileSize = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string", nullable=false)
     */
    private $fileName;

    /**
     * @var BaseFile
     * @Assert\File
     * @Vich\UploadableField(mapping="file", fileNameProperty="fileName", size="fileSize")
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="original_file_name", type="string", nullable=false)
     */
    private $originalFileName;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=5000, nullable=false)
     * @Assert\Length(max=5000)
     * @Assert\NotBlank
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", nullable=false)
     */
    private $hash;

    /**
     * @ORM\Column(name="meta", type="object", nullable=true)
     */
    private ?Meta $meta = null;

    /**
     * @ORM\Column(name="hidden", type="boolean", nullable=false, options={"default": "1"})
     */
    private bool $hidden = true;

    /**
     * @var Collection<FileTags>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FileTags", mappedBy="file", cascade={"persist", "remove"})
     */
    private $fileTags;

    /**
     * @var Collection<Tag>
     */
    private $tags;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * })
     */
    private $user;

    public function __construct()
    {
        $this->fileTags = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function __serialize(): array
    {
        $vars = \get_object_vars($this);
        $vars['file'] = $this->getFile()->getPathname();

        return $vars;
    }

    public function __unserialize(array $data): void
    {
        $this->file = new BaseFile($data['file'], false); //fix события (файлы могут быть удалены)
        unset($data['file']);

        foreach ($data as $key => $value) {
            if (\property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getMeta(): ?Meta
    {
        return $this->meta;
    }

    public function setMeta(Meta $meta = null): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @return Collection<FileTags>
     */
    public function getFileTags(): Collection
    {
        return $this->fileTags;
    }

    /**
     * @param Collection<FileTags> $fileTags
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
     * @return Collection<Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Set Tags
     * Использовать только для отображения. В БД этого поля нет.
     *
     * @param Collection<Tag> $tags
     *
     * @return File
     */
    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user = null): self
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
     */
    public function setBrowser($browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function getLastViewAt(): ?\DateTime
    {
        return $this->lastViewAt;
    }

    public function setLastViewAt(\DateTime $lastViewAt): self
    {
        $this->lastViewAt = $lastViewAt;

        return $this;
    }

    public function getCountViews(): int
    {
        return $this->countViews;
    }

    public function setCountViews(int $countViews): self
    {
        $this->countViews = $countViews;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword = null): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password = null): self
    {
        $this->password = $password;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        // по mime определять принадлежность к категории
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
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
        return \pathinfo($this->getOriginalFileName(), \PATHINFO_FILENAME);
    }

    /**
     * @return BaseFile
     */
    public function getFile()
    {
        return $this->file;
    }

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

    public function isImage(): bool
    {
        return \str_starts_with($this->getMimeType(), 'image/') || 'application/postscript' === $this->getMimeType() || 'application/illustrator' === $this->getMimeType() || 'application/vnd.adobe.illustrator' === $this->getMimeType();
    }

    public function isVideo(): bool
    {
        return \str_starts_with($this->getMimeType(), 'video/') || 'application/vnd.rn-realmedia' === $this->getMimeType() || 'application/vnd.rn-realmedia-vbr' === $this->getMimeType();
    }

    public function isAudio(): bool
    {
        return \str_starts_with($this->getMimeType(), 'audio/') && !$this->isPlaylist();
    }

    public function isText(): bool
    {
        return \str_starts_with($this->getMimeType(), 'text/');
    }

    public function isXml(): bool
    {
        return 'application/xml' === $this->getMimeType() || 'text/xml' === $this->getMimeType() || \str_contains($this->getMimeType(), '+xml');
    }

    public function isJson(): bool
    {
        return 'application/json' === $this->getMimeType();
    }

    public function isArchive(): bool
    {
        return \in_array($this->getMimeType(), [
            'application/zip', 'application/x-zip', 'application/x-zip-compressed', // zip
            'application/x-rar-compressed', 'application/x-rar', 'application/vnd.rar', // rar
            'application/x-bzip', // bz
            'application/x-bzip2', 'application/x-bz2', // bz2
            'application/x-7z-compressed', // 7z
            'application/x-tar', // tar
            'application/vnd.ms-cab-compressed', 'zz-application/zz-winassoc-cab', // cab
            'application/x-cd-image', 'application/x-gamecube-iso-image', 'application/x-gamecube-rom', 'application/x-iso9660-image', 'application/x-saturn-rom', 'application/x-sega-cd-rom', 'application/x-wbfs', 'application/x-wia', 'application/x-wii-iso-image', 'application/x-wii-rom', // iso
            'application/x-gzip', 'application/gzip', // gz
            'application/x-ace', 'application/x-ace-compressed', // ace
            'application/x-lha', 'application/x-lzh-compressed', // lzh
        ], true);
    }

    public function isFlash(): bool
    {
        return 'application/x-shockwave-flash' === $this->getMimeType() ||
            'application/futuresplash' === $this->getMimeType() ||
            'application/vnd.adobe.flash.movie' === $this->getMimeType();
    }

    public function isJavaApp(): bool
    {
        return 'application/java-archive' === $this->getMimeType() ||
            'application/x-java-archive' === $this->getMimeType() ||
            'application/x-jar' === $this->getMimeType();
    }

    public function isAndroidApp(): bool
    {
        return 'application/vnd.android.package-archive' === $this->getMimeType() ||
            (\in_array($this->getMimeType(), ['application/java-archive', 'application/zip', 'application/x-zip', 'application/x-zip-compressed'], true) && \str_ends_with($this->getFileName(), '.apk'));
    }

    public function isSymbianApp(): bool
    {
        return 'application/vnd.symbian.install' === $this->getMimeType() || 'x-epoc/x-sisx-app' === $this->getMimeType();
    }

    public function isWindowsApp(): bool
    {
        return 'application/x-msdownload' === $this->getMimeType() || 'application/x-ms-dos-executable' === $this->getMimeType();
    }

    public function isPdf(): bool
    {
        return \in_array($this->getMimeType(), [
            'application/pdf',
            'application/acrobat',
            'application/nappdf',
            'application/x-pdf',
            'image/pdf',
        ], true);
    }

    public function isWord(): bool
    {
        return \in_array($this->getMimeType(), [
            'application/msword', 'application/vnd.ms-word', 'application/x-msword', 'zz-application/zz-winassoc-doc', // doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
        ], true);
    }

    public function isRtf(): bool
    {
        return 'application/rtf' === $this->getMimeType() || 'text/rtf' === $this->getMimeType();
    }

    public function isExcel(): bool
    {
        return \in_array($this->getMimeType(), [
            'application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'zz-application/zz-winassoc-xls', // xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
        ], true);
    }

    public function is3gp(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/3gpp',
            'audio/3gpp-encrypted',
            'audio/x-rn-3gpp-amr',
            'audio/x-rn-3gpp-amr-encrypted',
            'audio/x-rn-3gpp-amr-wb',
            'audio/x-rn-3gpp-amr-wb-encrypted',
            'video/3gp',
            'video/3gpp',
            'video/3gpp-encrypted',

            'audio/3gpp2', 'video/3gpp2', // 3gpp2
        ], true);
    }

    public function isAvi(): bool
    {
        return \in_array($this->getMimeType(), [
            'video/avi',
            'video/divx',
            'video/msvideo',
            'video/vnd.divx',
            'video/x-avi',
            'video/x-msvideo',
        ], true);
    }

    public function isWmv(): bool
    {
        return \in_array($this->getMimeType(), [
            'video/x-ms-wmv',
            'audio/x-ms-wmv',
            'video/x-ms-asf', // https://wapinet.ru/file/54193
        ], true);
    }

    public function isMp3(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/mpeg',
            'audio/mp3',
            'audio/x-mp3',
            'audio/x-mpeg',
            'audio/x-mpg',
        ], true);
    }

    public function isM4a(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/mp4',
            'audio/m4a',
            'audio/x-m4a',
        ], true);
    }

    public function isM4v(): bool
    {
        return \in_array($this->getMimeType(), [
            'video/mp4',
            'video/mp4v-es',
            'video/x-m4v',
        ], true);
    }

    public function isOga(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/ogg',
            'audio/vorbis',
            'audio/x-flac+ogg',
            'audio/x-ogg',
            'audio/x-oggflac',
            'audio/x-speex+ogg',
            'audio/x-vorbis',
            'audio/x-vorbis+ogg',
        ], true);
    }

    public function isOgv(): bool
    {
        return 'video/ogg' === $this->getMimeType() || 'video/x-ogg' === $this->getMimeType();
    }

    public function isWebma(): bool
    {
        return 'audio/webm' === $this->getMimeType();
    }

    public function isWebmv(): bool
    {
        return 'video/webm' === $this->getMimeType();
    }

    public function isWav(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/wav',
            'audio/vnd.wave',
            'audio/x-wav',
        ], true);
    }

    public function isAmr(): bool
    {
        return 'audio/amr' === $this->getMimeType() || 'audio/amr-encrypted' === $this->getMimeType();
    }

    public function isFla(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/x-flv',
            'application/x-flash-audio',
            'audio/flv',
        ], true);
    }

    public function isFlv(): bool
    {
        return \in_array($this->getMimeType(), [
            'video/x-flv',
            'application/x-flash-video',
            'flv-application/octet-stream',
            'video/flv',
        ], true);
    }

    public function isFlac(): bool
    {
        return 'audio/x-flac' === $this->getMimeType() || 'audio/flac' === $this->getMimeType();
    }

    public function isTorrent(): bool
    {
        return 'application/x-bittorrent' === $this->getMimeType();
    }

    public function isMidi(): bool
    {
        return 'audio/midi' === $this->getMimeType() || 'audio/x-midi' === $this->getMimeType();
    }

    public function isPlaylist(): bool
    {
        return \in_array($this->getMimeType(), [
            'audio/x-mpegurl',
            'application/m3u',
            'application/vnd.apple.mpegurl',
            'audio/m3u',
            'audio/mpegurl',
            'audio/x-m3u',
            'audio/x-mp3-playlist',
        ], true);
    }

    public function isPlayableVideo(): bool
    {
        return $this->isVideo() && (
            $this->isM4v() ||
            $this->isOgv() ||
            $this->isWebmv() ||
            $this->isFlv() ||
            $this->is3gp() ||
            $this->isAvi() ||
            $this->isWmv() ||
            $this->isMov()
        );
    }

    public function isMov(): bool
    {
        return 'video/quicktime' === $this->getMimeType();
    }

    public function isPlayableAudio(): bool
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

    public function isExtractableArchive(): bool
    {
        return $this->isArchive();
    }

    public function __toString(): string
    {
        return (string) $this->getOriginalFileName();
    }
}
