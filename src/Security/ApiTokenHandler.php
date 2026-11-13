<?php

namespace App\Security;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private ApiTokenRepository $apiTokens)
    {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $token = $this->apiTokens->findActiveByHash(ApiToken::hash($accessToken));

        if (null === $token) {
            throw new BadCredentialsException('Invalid or expired API token.');
        }

        $user = $token->getUser();

        if (!$user->isEnabled()) {
            throw new BadCredentialsException('User is disabled.');
        }

        return new UserBadge($user->getUserIdentifier(), static fn (): \App\Entity\User => $user);
    }
}
