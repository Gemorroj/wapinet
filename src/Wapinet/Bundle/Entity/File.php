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
        /*
         // пример в контроллере
         $factory = $this->get('security.encoder_factory');
        $user = new Acme\UserBundle\Entity\User();

        $encoder = $factory->getEncoder($user);
        $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
        $user->setPassword($password);
         */
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
}
