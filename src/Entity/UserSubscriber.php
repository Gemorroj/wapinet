<?php

namespace App\Entity;

use App\Repository\UserSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table]
#[ORM\Entity(repositoryClass: UserSubscriberRepository::class)]
class UserSubscriber extends \ArrayObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $emailNews = true;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private bool $emailFriends = true;

    #[ORM\OneToOne(inversedBy: 'subscriber', targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(referencedColumnName: 'id', unique: true, nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEmailFriends(): bool
    {
        return $this->emailFriends;
    }

    public function setEmailFriends(bool $emailFriends): self
    {
        $this->emailFriends = $emailFriends;

        return $this;
    }

    public function getEmailNews(): bool
    {
        return $this->emailNews;
    }

    public function setEmailNews(bool $emailNews): self
    {
        $this->emailNews = $emailNews;

        return $this;
    }

    /**
     * @return \ArrayIterator<string, array<string, string|bool>>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([
            'emailNews' => [
                'name' => 'Новости',
                'enabled' => $this->getEmailNews(),
            ],
            'emailFriends =' => [
                'name' => 'События друзей',
                'enabled' => $this->getEmailFriends(),
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
