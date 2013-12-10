<?php

namespace Wapinet\Bundle\Entity;

use Wapinet\UserBundle\Entity\User;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * File
 * @Vich\Uploadable
 */
class File
{
    /**
     * @var integer
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
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;
    /**
     * @var \DateTime|null
     */
    protected $lastDownloadAt;
    /**
     * @var \DateTime|null
     */
    protected $lastViewAt;
    /**
     * @var int
     */
    protected $countDownloads = 0;
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
     * @var string
     */
    protected $mimeType;
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @Assert\File()
     * @Vich\UploadableField(mapping="file", fileNameProperty="fileName")
     *
     * @var File
     */
    protected $file;
    /**
     * @var string
     */
    protected $description;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return File
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get user
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return File
     */
    public function setUser(User $user = null)
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
     * @return File
     */
    public function setIp($ip)
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
     * @return File
     */
    public function setBrowser($browser)
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
     * @return File
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return File
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastDownloadAt()
    {
        return $this->lastDownloadAt;
    }

    /**
     * @param \DateTime $lastDownloadAt
     * @return File
     */
    public function setLastDownloadAt(\DateTime $lastDownloadAt)
    {
        $this->lastDownloadAt = $lastDownloadAt;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastViewAt()
    {
        return $this->lastViewAt;
    }

    /**
     * @param \DateTime $lastViewAt
     * @return File
     */
    public function setLastViewAt(\DateTime $lastViewAt)
    {
        $this->lastViewAt = $lastViewAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getCountDownloads()
    {
        return $this->countDownloads;
    }

    /**
     * @param int $countDownloads
     * @return File
     */
    public function setCountDownloads($countDownloads)
    {
        $this->countDownloads = $countDownloads;

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
     * @return File
     */
    public function setCountViews($countViews)
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
    public function setSaltValue()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

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
     * @return File
     */
    public function setPassword($password = null)
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
     * @return File
     */
    public function setMimeType($mimeType)
    {
        // по mime определять принадлежность к категории
        $this->mimeType = $mimeType;

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
     * @return File
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
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
    public function setFile(BaseFile $file)
    {
        $tmp = $this->file;

        $this->file = $file;

        if ($this->file !== $tmp) {
            $this->setUpdatedAtValue();
        }

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
     * @return File
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        return (0 === strpos($this->getMimeType(), 'image/'));
    }

    /**
     * @return bool
     */
    public function isVideo()
    {
        return (0 === strpos($this->getMimeType(), 'video/'));
    }

    /**
     * @return bool
     */
    public function isAudio()
    {
        return (0 === strpos($this->getMimeType(), 'audio/'));
    }

    /**
     * @return bool
     */
    public function isText()
    {
        return (0 === strpos($this->getMimeType(), 'text/'));
    }

    /**
     * @return bool
     */
    public function isArchive()
    {
        return in_array($this->getMimeType(), array(
                'application/zip', // zip
                'application/x-rar-compressed', // rar
                'application/x-bzip', // bz
                'application/x-bzip2', // bz2
                'application/x-7z-compressed', // 7z
                'application/x-tar', // tar
                'application/vnd.ms-cab-compressed', // cab
                'application/x-iso9660-image', // iso
                'application/x-gzip', // gz
                'application/x-ace-compressed', // ace
                'application/x-lzh-compressed', // lzh
            ));
    }

    /**
     * @return bool
     */
    public function isFlash()
    {
        return ('application/x-shockwave-flash' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isJavaApp()
    {
        return ('application/java-archive' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isAndroidApp()
    {
        return ('application/vnd.android.package-archive' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isSymbianApp()
    {
        return ('application/vnd.symbian.install' === $this->getMimeType() || 'x-epoc/x-sisx-app' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isWindowsApp()
    {
        return ('application/x-msdownload' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isPdf()
    {
        return ('application/pdf' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isWord()
    {
        return ('application/vnd.openxmlformats-officedocument.wordprocessingml.document' === $this->getMimeType() || 'application/msword' === $this->getMimeType());
    }

    /**
     * @return bool
     */
    public function isExcel()
    {
        return ('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' === $this->getMimeType() || 'application/vnd.ms-excel' === $this->getMimeType());
    }
}
