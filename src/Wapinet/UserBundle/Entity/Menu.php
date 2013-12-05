<?php
namespace Wapinet\UserBundle\Entity;

/**
 * Menu
 */
class Menu
{
    const ROUTE_FORUM = 'forum_index';
    const ROUTE_FILES = 'files_index';
    const ROUTE_ARCHIVER = 'archiver_index';
    const ROUTE_PROXY = 'proxy_index';
    const ROUTE_DOWNLOADS = 'downloads_index';
    const ROUTE_UTILITIES = 'utilities_index';
    const ROUTE_PROGRAMMING = 'programming_index';

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
     * @param boolean $forum
     * @return Menu
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
     * @return Menu
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
     * @return Menu
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
     * @return Menu
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
     * @return Menu
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
     * @return Menu
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
     * @return Menu
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
}
