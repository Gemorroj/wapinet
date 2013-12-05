<?php
namespace Wapinet\UserBundle\Entity;

/**
 * Panel
 */
class Panel extends \ArrayObject
{
    const ROUTE_FORUM = 'forum_index';
    const ROUTE_FILES = 'files_index';
    const ROUTE_ARCHIVER = 'archiver_index';
    const ROUTE_PROXY = 'proxy_index';
    const ROUTE_DOWNLOADS = 'downloads';
    const ROUTE_UTILITIES = 'utilities';
    const ROUTE_PROGRAMMING = 'programming';

    protected $id;
    protected $forum = true;
    protected $files = true;
    protected $archiver = false;
    protected $proxy = false;
    protected $downloads = true;
    protected $utilities = true;
    protected $programming = true;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getIterator()
    {
        return new \ArrayIterator(array(
            self::ROUTE_FORUM => array(
                'route' => self::ROUTE_FORUM,
                'name' => 'Форум',
                'enabled' => $this->getForum(),
            ),
            self::ROUTE_FILES => array(
                'route' => self::ROUTE_FILES,
                'name' => 'Файлообменник',
                'enabled' => $this->getFiles(),
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
     * @param boolean $files
     * @return Panel
     */
    public function setFiles($files)
    {
        $this->files = (bool)$files;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = '';
        foreach ($this->getIterator() as $item) {
            if ($item['enabled']) {
                $result .= $item['name'] . ', ';
            }
        }

        return rtrim($result, ', ');
    }
}
