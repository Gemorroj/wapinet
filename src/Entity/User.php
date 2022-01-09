<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 */
class User implements UserInterface, EquatableInterface, LegacyPasswordAuthenticatedUserInterface, \Stringable
{
    public const LIFETIME = '5 minutes';
    public const SEX_MALE = 'm';
    public const SEX_FEMALE = 'f';

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @ORM\Column(name="enabled", type="boolean", nullable=false, options={"default": "1"})
     */
    private bool $enabled = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private ?\DateTime $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=180, nullable=false, unique=true)
     * @Assert\Length(min=3, max=180)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=180, nullable=false, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="salt", type="string", length=255, nullable=true)
     */
    private ?string $salt = null;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @var string[]|null
     * @ORM\Column(name="roles", type="array", nullable=true)
     */
    private ?array $roles = null;

    /**
     * @ORM\Column(name="last_activity", type="datetime", nullable=true)
     */
    private ?\DateTime $lastActivity;

    /**
     * @ORM\Column(name="sex", type="string", nullable=true, columnDefinition="ENUM('m', 'f') DEFAULT NULL")
     */
    private ?string $sex;

    /**
     * @ORM\Column(name="birthday", type="date", nullable=true)
     */
    private ?\DateTime $birthday;

    /**
     * @ORM\Column(name="info", type="string", length=5000, nullable=true)
     * @Assert\Length(max=5000)
     */
    private ?string $info;

    /**
     * @ORM\Column(name="timezone", type="string", nullable=true)
     */
    private ?string $timezone;

    /**
     * @ORM\Column(name="country", type="string", nullable=true)
     */
    private ?string $country;

    /**
     * @ORM\Column(name="vk", type="string", nullable=true)
     */
    private ?string $vk;

    /**
     * @var Panel
     *
     * @ORM\OneToOne(targetEntity="Panel", mappedBy="user", cascade={"persist", "remove"})
     */
    private $panel;

    /**
     * @var Subscriber
     *
     * @ORM\OneToOne(targetEntity="Subscriber", mappedBy="user", cascade={"persist", "remove"})
     */
    private $subscriber;

    /**
     * @var Collection<Friend>
     *
     * @ORM\OneToMany(targetEntity="Friend", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({
     *     "id": "DESC"
     * })
     */
    private $friends;

    /**
     * @var Collection<Friend>
     *
     * @ORM\OneToMany(targetEntity="Friend", mappedBy="friend")
     * @ORM\OrderBy({
     *     "id": "DESC"
     * })
     */
    private $friended;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    public function __construct()
    {
        $this->friends = new ArrayCollection();
        $this->friended = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getUsername();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<Friend>
     */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    /**
     * @return Collection<Friend>
     */
    public function getFriended(): Collection
    {
        return $this->friended;
    }

    public function getVk(): ?string
    {
        return $this->vk;
    }

    public function setVk(?string $vk = null): self
    {
        $this->vk = $vk;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country = null): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountryName(): ?string
    {
        if (null !== $this->getCountry()) {
            return Countries::getName($this->getCountry());
        }

        return null;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone = null): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function isOnline(): bool
    {
        $lastActivity = $this->getLastActivity();

        return $lastActivity && ($lastActivity > new \DateTime('now -'.self::LIFETIME));
    }

    public function setPanel(Panel $panel): self
    {
        $this->panel = $panel;

        return $this;
    }

    public function getPanel(): Panel
    {
        if (!$this->panel) {
            $panel = new Panel();
            $panel->setUser($this);

            return $panel;
        }

        return $this->panel;
    }

    public function setSubscriber(Subscriber $subscriber): self
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    public function getSubscriber(): Subscriber
    {
        if (!$this->subscriber) {
            $subscriber = new Subscriber();
            $subscriber->setUser($this);

            return $subscriber;
        }

        return $this->subscriber;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(?string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function setBirthday(?\DateTime $birthday = null): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    public function setSex(?string $sex): self
    {
        if (self::SEX_MALE !== $sex && self::SEX_FEMALE !== $sex) {
            throw new \InvalidArgumentException('Invalid gender');
        }

        $this->sex = $sex;

        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue(): self
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function setLastActivity(?\DateTime $lastActivity): self
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    public function getLastActivity(): ?\DateTime
    {
        return $this->lastActivity;
    }

    public function isMale(): bool
    {
        return self::SEX_MALE === $this->getSex();
    }

    public function isFemale(): bool
    {
        return self::SEX_FEMALE === $this->getSex();
    }

    /**
     * @see UserInterface
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles ?? [];
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return \array_unique($roles);
    }

    /**
     * @param string[]|null $roles
     */
    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function setSalt(?string $salt): self
    {
        $this->salt = $salt;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->username;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getGravatarEmail(): ?string
    {
        return $this->getEmail();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        if ($this->email !== $user->getEmail()) {
            return false;
        }

        return true;
    }

    public function __serialize(): array
    {
        $data = \get_object_vars($this);
        $data['friends'] = null;
        $data['friended'] = null;
        $data['subscriber'] = null;
        $data['panel'] = null;

        return $data;
    }

    public function __unserialize(array $data): void
    {
        if (8 === \count($data)) {
            // Original FOSUser
            $this->password = $data[0];
            $this->salt = $data[1];
            $this->username = $data[3];
            $this->enabled = $data[4];
            $this->id = $data[5];
            $this->email = $data[6];

            return;
        }

        foreach ($data as $key => $value) {
            if (\property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function makeEncodedPassword(PasswordHasherFactoryInterface $passwordHasherFactory): void
    {
        $plainPassword = $this->getPlainPassword();
        if (null === $plainPassword || '' === $plainPassword) {
            return;
        }

        $passwordHasher = $passwordHasherFactory->getPasswordHasher($this);
        $hashedPassword = $passwordHasher->hash($plainPassword);

        $this->setSalt(null);

        $this->setPassword($hashedPassword);
        $this->eraseCredentials();
    }
}
