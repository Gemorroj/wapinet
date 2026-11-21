<?php

declare(strict_types=1);

namespace App\Service\Api\User;

use App\Dto\Api\User\EventResponse;
use App\Dto\Api\User\UserCounters;
use App\Dto\Api\User\UserListItem;
use App\Dto\Api\User\UserProfileResponse;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\GistRepository;
use App\Repository\UserFriendRepository;
use App\Service\Bbcode;
use App\Service\Vk;
use Symfony\Component\Clock\ClockInterface;

final readonly class UserPresenter
{
    public function __construct(
        private FileRepository $fileRepository,
        private GistRepository $gistRepository,
        private UserFriendRepository $friendRepository,
        private Bbcode $bbcode,
        private Vk $vk,
        private ClockInterface $clock,
    ) {
    }

    public function listItem(User $user): UserListItem
    {
        return new UserListItem(
            (int) $user->getId(),
            $user->getUsername(),
            $this->avatarUrl($user),
            $user->isOnline($this->clock->now()),
        );
    }

    public function profile(User $user, ?User $viewer, bool $withVk = true): UserProfileResponse
    {
        $info = $user->getInfo();

        return new UserProfileResponse(
            (int) $user->getId(),
            $user->getUsername(),
            $this->avatarUrl($user, 200),
            $user->isOnline($this->clock->now()),
            $user->getSex(),
            $user->getBirthday()?->format('Y-m-d'),
            $user->getCountry(),
            $user->getCountryName(),
            $user->getTimezone(),
            $this->toImmutable($user->getCreatedAt()),
            null !== $user->getLastActivity() ? $this->toImmutable($user->getLastActivity()) : null,
            $user->getVk(),
            $info,
            null !== $info ? $this->bbcode->render($info) : null,
            null !== $viewer && null !== $this->friendRepository->getFriend($viewer, $user),
            null !== $viewer && $viewer->getId() === $user->getId(),
            new UserCounters(
                $this->fileRepository->countUser($user),
                $this->gistRepository->countUser($user),
                $this->friendRepository->getFriendsCount($user),
            ),
            $withVk ? $this->vk->profile($user->getVk()) : null,
        );
    }

    public function event(Event $event): EventResponse
    {
        return new EventResponse(
            (int) $event->getId(),
            $this->toImmutable($event->getCreatedAt()),
            $event->getSubject(),
            $event->getTemplate(),
            $this->eventUrl($event),
        );
    }

    private function eventUrl(Event $event): ?string
    {
        $variables = $event->getVariables();

        return match ($event->getTemplate()) {
            'friend_file_add' => $this->urlFor('file', $variables, '/files/{id}'),
            'friend_gist_add' => $this->urlFor('gist', $variables, '/gist/view/{id}'),
            'news' => $this->urlFor('news', $variables, '/news/{id}'),
            'friend_add', 'friend_delete' => $this->urlFor('friend', $variables, '/user/profile/{username}'),
            'friend_friend_add', 'friend_friend_delete' => $this->urlFor('friend_friend', $variables, '/user/profile/{username}'),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function urlFor(string $key, array $variables, string $pattern): ?string
    {
        $data = $variables[$key] ?? null;

        if (!\is_array($data)) {
            return null;
        }

        $id = $data['id'] ?? null;
        $username = $data['username'] ?? null;

        if (\str_contains($pattern, '{username}')) {
            return \is_string($username) && '' !== $username
                ? \str_replace('{username}', \rawurlencode($username), $pattern)
                : null;
        }

        return \is_int($id) || \is_string($id)
            ? \str_replace('{id}', (string) $id, $pattern)
            : null;
    }

    private function avatarUrl(User $user, int $size = 80): string
    {
        return '//gravatar.com/avatar/'.\md5($user->getEmail()).'?d=mm&s='.$size;
    }

    private function toImmutable(\DateTimeInterface $dateTime): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($dateTime);
    }
}
