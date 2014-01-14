<?php
namespace Wapinet\UserBundle\Entity;

/**
 * Panel
 */
class Panel extends \ArrayObject
{
    const ROUTE_FORUM = 'forum_index';
    const ROUTE_GIST = 'gist_index';
    const ROUTE_FILE = 'file_index';
    const ROUTE_ARCHIVER = 'archiver_index';
    const ROUTE_PROXY = 'proxy_index';
    const ROUTE_DOWNLOADS = 'downloads';
    const ROUTE_UTILITIES = 'utilities';
    const ROUTE_PROGRAMMING = 'programming';

    protected $id;
    protected $forum = true;
    protected $gist = true;
    protected $file = true;
    protected $archiver = false;
    protected $proxy = false;
    protected $downloads = true;
    protected $utilities = true;
    protected $programming = true;
    /**
     * @var User
     */
    protected $user;

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
     * @param User $user
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
        return new \ArrayIterator(array(
            self::ROUTE_FORUM => array(
                'route' => self::ROUTE_FORUM,
                'name' => 'Форум',
                'enabled' => $this->getForum(),
            ),
            self::ROUTE_GIST => array(
                'route' => self::ROUTE_GIST,
                'name' => 'Блоги',
                'enabled' => $this->getGist(),
            ),
            self::ROUTE_FILE => array(
                'route' => self::ROUTE_FILE,
                'name' => 'Файлообменник',
                'enabled' => $this->getFile(),
            ),
            self::ROUTE_ARCHIVER => array(
                'route' => self::ROUTE_ARCHIVER,
                'name' => 'Архиватор',
                'enabled' => $this->getArchiver(),
            ),
            self::ROUTE_PROXY => array(
                'route' => self::ROUTE_PROXY,
                'name' => 'Анонимайзер',
                'enabled' => $this->getProxy(),
            ),
            self::ROUTE_DOWNLOADS => array(
                'route' => self::ROUTE_DOWNLOADS,
                'name' => 'Загрузки, развлечения',
                'enabled' => $this->getDownloads(),
            ),
            self::ROUTE_UTILITIES => array(
                'route' => self::ROUTE_UTILITIES,
                'name' => 'Полезные WEB приложения',
                'enabled' => $this->getUtilities(),
            ),
            self::ROUTE_PROGRAMMING => array(
                'route' => self::ROUTE_PROGRAMMING,
                'name' => 'WEB мастерская',
                'enabled' => $this->getProgramming(),
            ),
        ));
    }

    /**
     * @param boolean $forum
     * @return Panel
     */
    public function setForum($forum)
    {
        $this->forum = (bool)$forum;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getForum()
    {
        return $this->forum;
    }

    /**
     * @param boolean $downloads
     * @return Panel
     */
    public function setDownloads($downloads)
    {
        $this->downloads = (bool)$downloads;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getDownloads()
    {
        return $this->downloads;
    }

    /**
     * @param boolean $programming
     * @return Panel
     */
    public function setProgramming($programming)
    {
        $this->programming = (bool)$programming;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getProgramming()
    {
        return $this->programming;
    }

    /**
     * @param boolean $proxy
     * @return Panel
     */
    public function setProxy($proxy)
    {
        $this->proxy = (bool)$proxy;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param boolean $utilities
     * @return Panel
     */
    public function setUtilities($utilities)
    {
        $this->utilities = (bool)$utilities;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getUtilities()
    {
        return $this->utilities;
    }

    /**
     * @param boolean $archiver
     * @return Panel
     */
    public function setArchiver($archiver)
    {
        $this->archiver = (bool)$archiver;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getArchiver()
    {
        return $this->archiver;
    }

    /**
     * @param boolean $gist
     * @return Panel
     */
    public function setGist($gist)
    {
        $this->gist = (bool)$gist;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getGist()
    {
        return $this->gist;
    }

    /**
     * @param boolean $file
     * @return Panel
     */
    public function setFile($file)
    {
        $this->file = (bool)$file;

        return $this;
    }

    /**
     * @return boolean
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
                $result .= $item['name'] . ', ';
            }
        }

        return rtrim($result, ', ');
    }
}
