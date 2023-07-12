<?php

namespace App\Entity;

use App\Repository\UserSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: UserSubscriberRepository::class)]
class UserSubscriber implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $emailNews = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $emailFriends = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isEmailFriends(): bool
    {
        return $this->emailFriends;
    }

    public function setEmailFriends(bool $emailFriends): self
    {
        $this->emailFriends = $emailFriends;

        return $this;
    }

    public function isEmailNews(): bool
    {
        return $this->emailNews;
    }

    public function setEmailNews(bool $emailNews): self
    {
        $this->emailNews = $emailNews;

        return $this;
    }

    /**
     * @return \ArrayIterator<string, array{name: string, enabled: bool}>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([
            'emailNews' => [
                'name' => 'Новости',
                'enabled' => $this->isEmailNews(),
            ],
            'emailFriends =' => [
                'name' => 'События друзей',
                'enabled' => $this->isEmailFriends(),
            ],
        ]);
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
