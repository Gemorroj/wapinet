<?php

namespace Wapinet\Bundle\Entity;

/**
 * Image
 */
class Image extends FileUrl
{
    /**
     * @var integer
     */
    private $id;


    /**
     * @var string
     */
    private $path;


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
     * @return Image
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Image
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }
}
