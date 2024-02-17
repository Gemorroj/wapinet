<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'name_idx', columns: ['name'])]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Tag implements \Stringable, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name = '';

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $count = 0;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function jsonSerialize(): array
    {
        return \get_object_vars($this);
    }
}
