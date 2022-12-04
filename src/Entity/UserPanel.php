<?php

namespace App\Entity;

use App\Repository\UserPanelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: UserPanelRepository::class)]
class UserPanel extends \ArrayObject
{
    public const ROUTE_FORUM = 'forum_index';
    public const ROUTE_GUESTBOOK = 'guestbook_index';
    public const ROUTE_GIST = 'gist_index';
    public const ROUTE_FILE = 'file_index';
    public const ROUTE_ARCHIVER = 'archiver_index';
    public const ROUTE_DOWNLOADS = 'downloads';
    public const ROUTE_UTILITIES = 'utilities';
    public const ROUTE_PROGRAMMING = 'programming';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $forum = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $guestbook = false;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $gist = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $file = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $archiver = false;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $downloads = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $utilities = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $programming = true;

    #[ORM\OneToOne(inversedBy: 'panel', targetEntity: User::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', unique: true, nullable: false)]
    private User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return \ArrayIterator<string, array{route: string, name: string, enabled: bool}>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([
            self::ROUTE_FORUM => [
                'route' => self::ROUTE_FORUM,
                'name' => 'Форум',
                'enabled' => $this->getForum(),
            ],
            self::ROUTE_GUESTBOOK => [
                'route' => self::ROUTE_GUESTBOOK,
                'name' => 'Гостевая',
                'enabled' => $this->getGuestbook(),
            ],
            self::ROUTE_GIST => [
                'route' => self::ROUTE_GIST,
                'name' => 'Блоги',
                'enabled' => $this->getGist(),
            ],
            self::ROUTE_FILE => [
                'route' => self::ROUTE_FILE,
                'name' => 'Файлообменник',
                'enabled' => $this->getFile(),
            ],
            self::ROUTE_ARCHIVER => [
                'route' => self::ROUTE_ARCHIVER,
                'name' => 'Архиватор',
                'enabled' => $this->getArchiver(),
            ],
            self::ROUTE_DOWNLOADS => [
                'route' => self::ROUTE_DOWNLOADS,
                'name' => 'Развлечения',
                'enabled' => $this->getDownloads(),
            ],
            self::ROUTE_UTILITIES => [
                'route' => self::ROUTE_UTILITIES,
                'name' => 'Утилиты',
                'enabled' => $this->getUtilities(),
            ],
            self::ROUTE_PROGRAMMING => [
                'route' => self::ROUTE_PROGRAMMING,
                'name' => 'WEB мастерская',
                'enabled' => $this->getProgramming(),
            ],
        ]);
    }

    public function setForum(bool $forum): self
    {
        $this->forum = $forum;

        return $this;
    }

    public function getForum(): bool
    {
        return $this->forum;
    }

    public function setGuestbook(bool $guestbook): self
    {
        $this->guestbook = $guestbook;

        return $this;
    }

    public function getGuestbook(): bool
    {
        return $this->guestbook;
    }

    public function setDownloads(bool $downloads): self
    {
        $this->downloads = $downloads;

        return $this;
    }

    public function getDownloads(): bool
    {
        return $this->downloads;
    }

    public function setProgramming(bool $programming): self
    {
        $this->programming = $programming;

        return $this;
    }

    public function getProgramming(): bool
    {
        return $this->programming;
    }

    public function setUtilities(bool $utilities): self
    {
        $this->utilities = $utilities;

        return $this;
    }

    public function getUtilities(): bool
    {
        return $this->utilities;
    }

    public function setArchiver(bool $archiver): self
    {
        $this->archiver = $archiver;

        return $this;
    }

    public function getArchiver(): bool
    {
        return $this->archiver;
    }

    public function setGist(bool $gist): self
    {
        $this->gist = $gist;

        return $this;
    }

    public function getGist(): bool
    {
        return $this->gist;
    }

    public function setFile(bool $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFile(): bool
    {
        return $this->file;
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->getIterator() as $item) {
            if (true === $item['enabled']) {
                $result .= $item['name'].', ';
            }
        }

        return \rtrim($result, ', ');
    }
}
