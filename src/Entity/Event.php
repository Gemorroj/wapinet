<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $subject = '';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $variables = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $template = '';

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $needEmail = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setVariables(?array $variables = null): self
    {
        $this->variables = $variables;

        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables ?? [];
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

    public function setNeedEmail(bool $needEmail): self
    {
        $this->needEmail = $needEmail;

        return $this;
    }

    public function getNeedEmail(): bool
    {
        return $this->needEmail;
    }
}
