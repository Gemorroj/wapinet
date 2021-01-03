<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Panel.
 *
 * @ORM\Table(name="user_panel")
 * @ORM\Entity
 */
class Panel extends \ArrayObject
{
    public const ROUTE_FORUM = 'forum_index';
    public const ROUTE_GUESTBOOK = 'guestbook_index';
    public const ROUTE_GIST = 'gist_index';
    public const ROUTE_FILE = 'file_index';
    public const ROUTE_ARCHIVER = 'archiver_index';
    public const ROUTE_DOWNLOADS = 'downloads';
    public const ROUTE_UTILITIES = 'utilities';
    public const ROUTE_PROGRAMMING = 'programming';

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="forum", type="boolean", nullable=false)
     */
    private $forum = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="guestbook", type="boolean", nullable=false)
     */
    private $guestbook = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="gist", type="boolean", nullable=false)
     */
    private $gist = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="file", type="boolean", nullable=false)
     */
    private $file = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="archiver", type="boolean", nullable=false)
     */
    private $archiver = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="downloads", type="boolean", nullable=false)
     */
    private $downloads = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="utilities", type="boolean", nullable=false)
     */
    private $utilities = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="programming", type="boolean", nullable=false)
     */
    private $programming = true;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="panel", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true, nullable=false)
     * })
     */
    private $user;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Panel
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator([
            self::ROUTE_FORUM => [
                'route' => self::ROUTE_FORUM,
                'name' => 'Форум',
                'enabled' => $this->getForum(),
            ],
            self::ROUTE_GUESTBOOK => [
                'route' => self::ROUTE_GUESTBOOK,
                'name' => 'Гостевая',
                'enabled' => $this->getGuestbook(),
            ],
            self::ROUTE_GIST => [
                'route' => self::ROUTE_GIST,
                'name' => 'Блоги',
                'enabled' => $this->getGist(),
            ],
            self::ROUTE_FILE => [
                'route' => self::ROUTE_FILE,
                'name' => 'Файлообменник',
                'enabled' => $this->getFile(),
            ],
            self::ROUTE_ARCHIVER => [
                'route' => self::ROUTE_ARCHIVER,
                'name' => 'Архиватор',
                'enabled' => $this->getArchiver(),
            ],
            self::ROUTE_DOWNLOADS => [
                'route' => self::ROUTE_DOWNLOADS,
                'name' => 'Развлечения',
                'enabled' => $this->getDownloads(),
            ],
            self::ROUTE_UTILITIES => [
                'route' => self::ROUTE_UTILITIES,
                'name' => 'Утилиты',
                'enabled' => $this->getUtilities(),
            ],
            self::ROUTE_PROGRAMMING => [
                'route' => self::ROUTE_PROGRAMMING,
                'name' => 'WEB мастерская',
                'enabled' => $this->getProgramming(),
            ],
        ]);
    }

    /**
     * @param bool $forum
     *
     * @return Panel
     */
    public function setForum($forum)
    {
        $this->forum = (bool) $forum;

        return $this;
    }

    /**
     * @return bool
     */
    public function getForum()
    {
        return $this->forum;
    }

    /**
     * @param bool $guestbook
     *
     * @return Panel
     */
    public function setGuestbook($guestbook)
    {
        $this->guestbook = (bool) $guestbook;

        return $this;
    }

    /**
     * @return bool
     */
    public function getGuestbook()
    {
        return $this->guestbook;
    }

    /**
     * @param bool $downloads
     *
     * @return Panel
     */
    public function setDownloads($downloads)
    {
        $this->downloads = (bool) $downloads;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDownloads()
    {
        return $this->downloads;
    }

    /**
     * @param bool $programming
     *
     * @return Panel
     */
    public function setProgramming($programming)
    {
        $this->programming = (bool) $programming;

        return $this;
    }

    /**
     * @return bool
     */
    public function getProgramming()
    {
        return $this->programming;
    }

    /**
     * @param bool $utilities
     *
     * @return Panel
     */
    public function setUtilities($utilities)
    {
        $this->utilities = (bool) $utilities;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUtilities()
    {
        return $this->utilities;
    }

    /**
     * @param bool $archiver
     *
     * @return Panel
     */
    public function setArchiver($archiver)
    {
        $this->archiver = (bool) $archiver;

        return $this;
    }

    /**
     * @return bool
     */
    public function getArchiver()
    {
        return $this->archiver;
    }

    /**
     * @param bool $gist
     *
     * @return Panel
     */
    public function setGist($gist)
    {
        $this->gist = (bool) $gist;

        return $this;
    }

    /**
     * @return bool
     */
    public function getGist()
    {
        return $this->gist;
    }

    /**
     * @param bool $file
     *
     * @return Panel
     */
    public function setFile($file)
    {
        $this->file = (bool) $file;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = '';
        foreach ($this->getIterator() as $item) {
            if (true === $item['enabled']) {
                $result .= $item['name'].', ';
            }
        }

        return \rtrim($result, ', ');
    }
}
