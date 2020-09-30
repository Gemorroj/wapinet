<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileTags.
 *
 * @ORM\Table(name="file_tags")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class FileTags
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @var \App\Entity\File
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\File", inversedBy="fileTags", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $file;

    /**
     * @var \App\Entity\Tag
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Tag", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tag_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tag;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return FileTags
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get file.
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file.
     *
     * @return FileTags
     */
    public function setFile(File $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get tag.
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set tag.
     *
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

    /**
     * @ORM\PrePersist
     */
    public function setPrePersistValue()
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setPreUpdateValue()
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    /**
     * @ORM\PreRemove
     */
    public function setPreRemoveValue()
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() - 1);

        return $this;
    }
}
