<?php

namespace WapinetBundle\Entity;


/**
 * File
 */
class FileTags
{
    /**
     * @var integer
     */
    private $id;
    /**
     * @var File
     */
    protected $file;
    /**
     * @var Tag
     */
    protected $tag;


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
     * @return FileTags
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    /**
     * Get file
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     *
     * @param File $file
     * @return FileTags
     */
    public function setFile(File $file)
    {
        $this->file = $file;

        return $this;
    }


    /**
     * Get tag
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set tag
     *
     * @param Tag $tag
     * @return FileTags
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getTag()->getName();
    }

    public function setPrePersistValue()
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    public function setPreUpdateValue()
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    public function setPreRemoveValue()
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() - 1);

        return $this;
    }
}
