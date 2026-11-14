<?php

declare(strict_types=1);

namespace App\Service\Api\User;

use App\Dto\Api\User\ProfileUpdateRequest;
use App\Dto\Api\User\RegisterRequest;
use App\Dto\Api\User\SubscriberUpdateRequest;
use App\Entity\User;
use App\Entity\UserFriend;
use App\Entity\UserSubscriber;
use App\Message\FriendAddMessage;
use App\Message\FriendDeleteMessage;
use App\Repository\UserFriendRepository;
use App\Repository\UserRepository;
use App\Service\Api\RateLimit;
use App\Service\Captcha\CaptchaUnavailableException;
use App\Service\Captcha\CaptchaVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserFriendRepository $friendRepository,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private CaptchaVerifierInterface $captchaVerifier,
        #[Autowire(service: 'limiter.api_auth_register')]
        private RateLimiterFactory $registerLimiter,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function register(RegisterRequest $payload, Request $request): User
    {
        RateLimit::consumeForIp($this->registerLimiter, $request);

        try {
            $captchaValid = $this->captchaVerifier->verify($payload->captchaToken ?? '', $request->getClientIp());
        } catch (CaptchaUnavailableException $exception) {
            throw new ServiceUnavailableHttpException(null, 'Captcha service is unavailable.', $exception);
        }

        if (!$captchaValid) {
            throw new AccessDeniedHttpException('Captcha verification failed.');
        }

        if (null !== $this->userRepository->findOneBy(['username' => $payload->username])) {
            throw new UnprocessableEntityHttpException('Пользователь с таким username уже зарегистрирован.');
        }

        if (null !== $this->userRepository->findOneBy(['email' => $payload->email])) {
            throw new UnprocessableEntityHttpException('Пользователь с таким email уже зарегистрирован.');
        }

        $user = new User();
        $user->setUsername($payload->username);
        $user->setEmail($payload->email);
        $user->setPlainPassword($payload->password);
        $user->makeEncodedPassword($this->passwordHasherFactory);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateProfile(User $user, ProfileUpdateRequest $payload): User
    {
        $user->setSex($payload->sex);
        $user->setBirthday(null !== $payload->birthday ? new \DateTimeImmutable($payload->birthday) : null);
        $user->setTimezone($payload->timezone);
        $user->setCountry($payload->country);
        $user->setVk(self::nullable($payload->vk));
        $user->setInfo(self::nullable($payload->info));
        $user->setUpdatedAtValue();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateSubscriber(User $user, SubscriberUpdateRequest $payload): void
    {
        $subscriber = $user->getSubscriber() ?? new UserSubscriber();
        $subscriber->setEmailNews($payload->emailNews);
        $subscriber->setEmailFriends($payload->emailFriends);
        $user->setSubscriber($subscriber);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function addFriend(User $user, User $friend): void
    {
        if ($user->getId() === $friend->getId()) {
            throw new UnprocessableEntityHttpException('Нельзя добавить себя в друзья.');
        }

        if (null !== $this->friendRepository->getFriend($user, $friend)) {
            throw new ConflictHttpException('Пользователь уже в друзьях.');
        }

        $userFriend = new UserFriend();
        $userFriend->setUser($user);
        $userFriend->setFriend($friend);
        $user->getFriends()->add($userFriend);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new FriendAddMessage((int) $user->getId(), (int) $friend->getId()));
    }

    public function removeFriend(User $user, User $friend): void
    {
        $userFriend = $this->friendRepository->getFriend($user, $friend);

        if (null === $userFriend) {
            throw new ConflictHttpException('Пользователь не в друзьях.');
        }

        $user->getFriends()->removeElement($userFriend);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new FriendDeleteMessage((int) $user->getId(), (int) $friend->getId()));
    }

    private static function nullable(?string $value): ?string
    {
        return null !== $value && '' !== \trim($value) ? \trim($value) : null;
    }
}
