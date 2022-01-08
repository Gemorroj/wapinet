<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileTags.
 *
 * @ORM\Table(name="file_tags")
 * @ORM\Entity(repositoryClass="App\Repository\FileTagsRepository")
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
     * @var File
     *
     * @ORM\ManyToOne(targetEntity="File", inversedBy="fileTags", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="file_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $file;

    /**
     * @var Tag
     *
     * @ORM\ManyToOne(targetEntity="Tag", cascade={"persist", "remove"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tag_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $tag;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     */
    public function setId($id): self
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

    public function setFile(File $file): self
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

    public function setTag(Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getTag()->getName();
    }

    /**
     * @ORM\PrePersist
     */
    public function setPrePersistValue(): self
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setPreUpdateValue(): self
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    /**
     * @ORM\PreRemove
     */
    public function setPreRemoveValue(): self
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() - 1);

        return $this;
    }
}
