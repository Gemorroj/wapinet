<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Api\User\VkProfile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Minimal VK API client for the profile block (public API requires an access token).
 */
final readonly class Vk
{
    private const string API_URL = 'https://api.vk.ru/method/users.get';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')]
        private CacheInterface $cache,
        #[Autowire('%env(VK_ACCESS_TOKEN)%')]
        private string $accessToken,
    ) {
    }

    public function profile(?string $vk): ?VkProfile
    {
        $vk = \trim((string) $vk);

        if ('' === $vk || 'null' === $this->accessToken) {
            return null;
        }

        return $this->cache->get('vk_profile_'.\md5($vk), function () use ($vk): ?VkProfile {
            try {
                $response = $this->httpClient->request('GET', self::API_URL, [
                    'query' => [
                        'user_ids' => $vk,
                        'fields' => 'online,photo_200',
                        'access_token' => $this->accessToken,
                        'v' => '5.199',
                    ],
                ])->toArray();
            } catch (\Throwable) {
                return null;
            }

            $user = $response['response'][0] ?? null;

            if (!\is_array($user)) {
                return null;
            }

            return new VkProfile(
                (int) ($user['id'] ?? 0),
                \trim((string) ($user['first_name'] ?? '').' '.(string) ($user['last_name'] ?? '')),
                (string) ($user['photo_200'] ?? ''),
                (bool) ($user['online'] ?? false),
            );
        });
    }
}
