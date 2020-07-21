<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\NativePasswordEncoder;
use Symfony\Component\Security\Core\Encoder\SelfSaltingEncoderInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, EquatableInterface, \Serializable
{
    const LIFETIME = '5 minutes';
    const SEX_MALE = 'm';
    const SEX_FEMALE = 'f';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    /**
     * @var \DateTime|null
     */
    protected $lastActivity;

    /**
     * @var string|null
     */
    protected $sex;

    /**
     * @var \DateTime|null
     */
    protected $birthday;

    /**
     * @var Panel
     */
    protected $panel;

    /**
     * @var Subscriber
     */
    protected $subscriber;

    /**
     * @var ArrayCollection
     */
    protected $friends;

    /**
     * @var ArrayCollection
     */
    protected $friended;

    /**
     * @var string
     */
    protected $info;

    /**
     * @var string|null
     */
    protected $timezone;

    /**
     * @var string|null
     */
    protected $country;

    /**
     * @var string|null
     */
    protected $vk;
    /**
     * @var array
     */
    protected $roles = [];
    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;
    /**
     * The salt to use for hashing.
     *
     * @var string
     */
    protected $salt;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $email;

    public function __construct()
    {
        $this->friends = new ArrayCollection();
        $this->friended = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getUsername();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getFriends()
    {
        return $this->friends;
    }

    /**
     * @return ArrayCollection
     */
    public function getFriended()
    {
        return $this->friended;
    }

    /**
     * @return string|null
     */
    public function getVk()
    {
        return $this->vk;
    }

    /**
     * @param string|null $vk
     *
     * @return User
     */
    public function setVk($vk = null)
    {
        $this->vk = $vk;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string|null $country
     *
     * @return User
     */
    public function setCountry($country = null)
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

    /**
     * @return string|null
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string|null $timezone
     *
     * @return User
     */
    public function setTimezone($timezone = null)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        $lastActivity = $this->getLastActivity();
        if (null !== $lastActivity) {
            if ($lastActivity > new \DateTime('now -'.self::LIFETIME)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return User
     */
    public function setPanel(Panel $panel)
    {
        $this->panel = $panel;

        return $this;
    }

    /**
     * @return Panel
     */
    public function getPanel()
    {
        if (null === $this->panel) {
            $panel = new Panel();
            $panel->setUser($this);

            return $panel;
        }

        return $this->panel;
    }

    /**
     * @return User
     */
    public function setSubscriber(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    /**
     * @return Subscriber
     */
    public function getSubscriber()
    {
        if (null === $this->subscriber) {
            $subscriber = new Subscriber();
            $subscriber->setUser($this);

            return $subscriber;
        }

        return $this->subscriber;
    }

    /**
     * @return string|null
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     *
     * @return User
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @param \DateTime $birthday
     *
     * @return User
     */
    public function setBirthday(\DateTime $birthday = null)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param string $sex
     *
     * @throws \InvalidArgumentException
     *
     * @return User
     */
    public function setSex($sex)
    {
        if (self::SEX_MALE !== $sex && self::SEX_FEMALE !== $sex) {
            throw new \InvalidArgumentException('Invalid sex');
        }

        $this->sex = $sex;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return User
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return User
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @return User
     */
    public function setLastActivity(\DateTime $lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    /**
     * @return bool
     */
    public function isMale()
    {
        return self::SEX_MALE === $this->getSex();
    }

    /**
     * @return bool
     */
    public function isFemale()
    {
        return self::SEX_FEMALE === $this->getSex();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return \array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
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

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
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

    public function isEqualTo(UserInterface $user)
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

    public function serialize(): string
    {
        $data = \get_object_vars($this);
        $data['friends'] = null;
        $data['friended'] = null;
        $data['subscriber'] = null;
        $data['panel'] = null;

        return \serialize($data);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        $data = \unserialize($serialized, ['allowed_classes' => true]);  // true для полного соответствия с поведением doctrine

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

    public function makeEncodedPassword(EncoderFactoryInterface $encoderFactory): void
    {
        $plainPassword = $this->getPlainPassword();
        if (null === $plainPassword || '' === $plainPassword) {
            return;
        }

        $encoder = $encoderFactory->getEncoder($this);

        if ($encoder instanceof NativePasswordEncoder || $encoder instanceof SelfSaltingEncoderInterface) {
            $this->setSalt(null);
        } else {
            $salt = \rtrim(\str_replace('+', '.', \base64_encode(\random_bytes(32))), '=');
            $this->setSalt($salt);
        }

        $hashedPassword = $encoder->encodePassword($plainPassword, $this->getSalt());
        $this->setPassword($hashedPassword);
        $this->eraseCredentials();
    }
}
