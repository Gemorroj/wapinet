<?php

declare(strict_types=1);

namespace App\Service\Api\Gist;

use App\Dto\Api\Gist\GistListItem;
use App\Dto\Api\Gist\GistResponse;
use App\Dto\Api\User\UserSummary;
use App\Entity\Gist;
use App\Entity\User;
use App\Security\GistVoter;
use App\Service\Bbcode;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class GistPresenter
{
    public function __construct(
        private Bbcode $bbcode,
        private ClockInterface $clock,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function toListItem(Gist $gist): GistListItem
    {
        $user = $gist->getUser();

        return new GistListItem(
            (int) $gist->getId(),
            $gist->getSubject(),
            $gist->getCreatedAt(),
            $gist->getUpdatedAt(),
            UserSummary::fromUser($user),
            $this->avatarUrl($user),
            $user->isOnline($this->clock->now()),
            $this->authorizationChecker->isGranted(GistVoter::EDIT, $gist),
            $this->authorizationChecker->isGranted(GistVoter::DELETE, $gist),
        );
    }

    public function toResponse(Gist $gist): GistResponse
    {
        $user = $gist->getUser();

        return new GistResponse(
            (int) $gist->getId(),
            $gist->getSubject(),
            $gist->getBody(),
            $this->bbcode->render($gist->getBody()),
            $gist->getCreatedAt(),
            $gist->getUpdatedAt(),
            UserSummary::fromUser($user),
            $this->avatarUrl($user),
            $user->isOnline($this->clock->now()),
            $this->authorizationChecker->isGranted(GistVoter::EDIT, $gist),
            $this->authorizationChecker->isGranted(GistVoter::DELETE, $gist),
        );
    }

    private function avatarUrl(User $user): string
    {
        return '//gravatar.com/avatar/'.\md5($user->getEmail()).'?d=mm&s=80';
    }
}
