<?php

namespace App\Entity;

use App\Repository\OnlineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(options: ['engine' => 'MEMORY'])]
#[ORM\UniqueConstraint(name: 'unique_idx', columns: ['ip', 'browser'])]
#[ORM\Entity(repositoryClass: OnlineRepository::class)]
class Online
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTime $datetime = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $ip = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $browser = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $path = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function setDatetime(\DateTime $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getDatetime(): \DateTime
    {
        return $this->datetime;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setBrowser(string $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }
}
