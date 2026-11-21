<?php

declare(strict_types=1);

namespace App\Service\Api\Guestbook;

use App\Dto\Api\Guestbook\GuestbookMessageResponse;
use App\Dto\Api\Network\IpInsightResponse;
use App\Dto\Api\User\UserSummary;
use App\Entity\Guestbook;
use App\Entity\User;
use App\Service\Bbcode;
use App\Service\Geoip2;
use App\Service\Network\IpInsightLookup;
use Symfony\Component\Clock\ClockInterface;

final readonly class GuestbookPresenter
{
    public function __construct(
        private Bbcode $bbcode,
        private ClockInterface $clock,
        private Geoip2 $geoip2,
        private IpInsightLookup $insightLookup,
    ) {
    }

    public function message(Guestbook $message): GuestbookMessageResponse
    {
        $user = $message->getUser();
        $country = null === $user ? $this->country($message->getIp()) : null;

        return new GuestbookMessageResponse(
            (int) $message->getId(),
            $message->getMessage(),
            $this->bbcode->render($message->getMessage()),
            $message->getCreatedAt(),
            null !== $user ? UserSummary::fromUser($user) : null,
            $this->avatarUrl($user),
            null !== $user && $user->isOnline($this->clock->now()),
            null === $user ? $message->getIp() : null,
            null === $user ? $message->getBrowser() : null,
            $country?->country->isoCode,
            $country?->country->names['ru'] ?? null,
        );
    }

    public function insight(Guestbook $message): ?IpInsightResponse
    {
        if (null !== $message->getUser() || '' === $message->getIp()) {
            return null;
        }

        return $this->insightLookup->lookup($message->getIp());
    }

    private function avatarUrl(?User $user): string
    {
        return '//gravatar.com/avatar/'.\md5($user?->getEmail() ?? '').'?d=mm&s=80';
    }

    private function country(?string $ip): ?\GeoIp2\Model\Country
    {
        if (null === $ip || '' === $ip) {
            return null;
        }

        try {
            return $this->geoip2->getCountry($ip);
        } catch (\Throwable) {
            return null;
        }
    }
}
