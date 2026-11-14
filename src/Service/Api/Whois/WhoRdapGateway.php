<?php

declare(strict_types=1);

namespace App\Service\Api\Whois;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use WhoRdap\NetworkClient\NetworkClient;
use WhoRdap\Transport\NativeTransport;
use WhoRdap\WhoRdap as WhoRdapClient;

final readonly class WhoRdapGateway implements WhoisGatewayInterface
{
    public function __construct(
        #[Autowire(service: 'cache.whordap')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function lookup(string $query, string $protocol): array
    {
        $client = $this->client();
        $errors = [];

        foreach ($this->protocols($protocol) as $current) {
            try {
                $result = 'rdap' === $current ? $client->processRdap($query) : $client->processWhois($query);
                $actual = $result->getActualResponse();

                return ['protocol' => $current, 'server' => $actual->server, 'response' => $actual->response];
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        throw new WhoisException('Не удалось получить данные: '.\implode('; ', \array_unique($errors)));
    }

    /**
     * @return list<string>
     */
    private function protocols(string $protocol): array
    {
        return 'auto' === $protocol ? ['rdap', 'whois'] : [$protocol];
    }

    private function client(): WhoRdapClient
    {
        $transport = new NativeTransport(timeout: 5);

        return new WhoRdapClient(new NetworkClient($this->cache, null, $transport));
    }
}
