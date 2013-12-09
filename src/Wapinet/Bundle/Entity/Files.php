<?php

namespace Wapinet\Bundle\Entity;

use Wapinet\UserBundle\Entity\User;

/**
 * Files
 */
class Files
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
    protected $countDownloads;
    /**
     * @var int
     */
    protected $countViews;
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
    protected $directoryPath;
    /**
     * @var string
     */
    protected $fileName;

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
     * @return Files
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
     * @return Files
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

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
     * @return Files
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
     * @return Files
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
     * @return Files
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
     * @return Files
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
     * @return Files
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
     * @return Files
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
     * @return Files
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
     * @return Files
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
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     * @return Files
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
    public function getDirectoryPath()
    {
        return $this->directoryPath;
    }

    /**
     * @param string $directoryPath
     * @return Files
     */
    public function setDirectoryPath($directoryPath)
    {
        $this->directoryPath = $directoryPath;

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
     * @return Files
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }
}
