<?php

namespace App\Entity;

use App\Repository\FileTagsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: FileTagsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FileTags implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: File::class, cascade: ['persist', 'remove'], inversedBy: 'fileTags')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?File $file = null;

    #[ORM\ManyToOne(targetEntity: Tag::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Tag $tag = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(File $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getTag(): ?Tag
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
        return (string) $this->getTag()?->getName();
    }

    #[ORM\PrePersist]
    public function setPrePersistValue(): self
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    #[ORM\PreUpdate]
    public function setPreUpdateValue(): self
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() + 1);

        return $this;
    }

    #[ORM\PreRemove]
    public function setPreRemoveValue(): self
    {
        $tag = $this->getTag();
        $tag->setCount($tag->getCount() - 1);

        return $this;
    }

    public function jsonSerialize(): array
    {
        return \get_object_vars($this);
    }
}
