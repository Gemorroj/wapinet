<?php

namespace App\Entity;

use App\Repository\UserFriendRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: UserFriendRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserFriend implements \Stringable, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: false)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'friends')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'friended')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private User $friend;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setFriend(User $friend): self
    {
        $this->friend = $friend;

        return $this;
    }

    public function getFriend(): User
    {
        return $this->friend;
    }

    public function __toString(): string
    {
        return (string) $this->getFriend();
    }

    public function jsonSerialize(): array
    {
        return \get_object_vars($this);
    }
}
