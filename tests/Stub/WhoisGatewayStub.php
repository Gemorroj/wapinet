<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Service\Api\Whois\WhoisGatewayInterface;

final class WhoisGatewayStub implements WhoisGatewayInterface
{
    public function lookup(string $query, string $protocol): array
    {
        return [
            'protocol' => 'rdap',
            'server' => 'https://rdap.example.test/',
            'response' => (string) \json_encode([
                'objectClassName' => 'domain',
                'ldhName' => 'example.com',
                'handle' => 'EXAMPLE-COM',
                'status' => ['active'],
                'events' => [
                    ['eventAction' => 'registration', 'eventDate' => '2020-01-01T00:00:00Z'],
                    ['eventAction' => 'expiration', 'eventDate' => '2030-01-01T00:00:00Z'],
                ],
                'nameservers' => [
                    ['ldhName' => 'NS1.EXAMPLE.COM'],
                    ['ldhName' => 'NS2.EXAMPLE.COM'],
                ],
                'secureDNS' => ['delegationSigned' => true],
                'entities' => [
                    [
                        'roles' => ['registrar'],
                        'vcardArray' => ['vcard', [
                            ['fn', new \stdClass(), 'text', 'Example Registrar'],
                            ['email', new \stdClass(), 'text', 'registrar@example.com'],
                        ]],
                    ],
                ],
            ], \JSON_THROW_ON_ERROR),
        ];
    }
}
