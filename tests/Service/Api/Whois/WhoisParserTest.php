<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Whois;

use App\Service\Api\Whois\WhoisParser;
use PHPUnit\Framework\TestCase;

final class WhoisParserTest extends TestCase
{
    public function testDetectsQueryKind(): void
    {
        $parser = new WhoisParser();

        static::assertSame('domain', $parser->kind('example.com'));
        static::assertSame('ipv4', $parser->kind('8.8.8.8'));
        static::assertSame('ipv6', $parser->kind('2001:4860:4860::8888'));
        static::assertSame('cidr', $parser->kind('8.8.8.0/24'));
        static::assertSame('asn', $parser->kind('AS15169'));
        static::assertSame('asn', $parser->kind('15169'));
    }

    public function testParsesDomainRdap(): void
    {
        $response = new WhoisParser()->parse(
            query: 'example.com',
            kind: 'domain',
            protocol: 'rdap',
            server: 'https://rdap.example.test/',
            raw: (string) \json_encode([
                'objectClassName' => 'domain',
                'ldhName' => 'example.com',
                'handle' => 'EXAMPLE-COM',
                'status' => ['active', 'client transfer prohibited'],
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
                            ['fn', [], 'text', 'Example Registrar'],
                            ['email', [], 'text', 'registrar@example.com'],
                        ]],
                    ],
                ],
            ], \JSON_THROW_ON_ERROR),
        );

        static::assertSame('domain', $response->kind);
        static::assertSame('rdap', $response->protocol);
        static::assertSame('example.com', $response->details[0]->value);
        static::assertSame('EXAMPLE-COM', $response->details[1]->value);
        static::assertSame('Подписан', $response->details[2]->value);
        static::assertSame(['active', 'client transfer prohibited'], $response->statuses);
        static::assertCount(2, $response->events);
        static::assertSame('Зарегистрирован', $response->events[0]->action);
        static::assertSame(['ns1.example.com', 'ns2.example.com'], $response->nameservers);
        static::assertSame('registrar', $response->contacts[0]->role);
        static::assertSame('Example Registrar', $response->contacts[0]->name);
        static::assertSame('registrar@example.com', $response->contacts[0]->email);
    }

    public function testParsesIpRdap(): void
    {
        $response = new WhoisParser()->parse(
            query: '8.8.8.8',
            kind: 'ipv4',
            protocol: 'rdap',
            server: 'https://rdap.arin.net/registry/',
            raw: (string) \json_encode([
                'objectClassName' => 'ip network',
                'handle' => 'NET-8-8-8-0-2',
                'name' => 'GOGL',
                'startAddress' => '8.8.8.0',
                'endAddress' => '8.8.8.255',
                'ipVersion' => 'v4',
                'type' => 'DIRECT ALLOCATION',
                'country' => 'US',
                'parentHandle' => 'NET-8-0-0-0-0',
                'cidr0_cidrs' => [['v4prefix' => '8.8.8.0', 'length' => 24]],
                'status' => ['active'],
                'events' => [],
            ], \JSON_THROW_ON_ERROR),
        );

        static::assertSame('ipv4', $response->kind);
        static::assertSame('GOGL', $response->details[0]->value);
        static::assertSame('8.8.8.0 — 8.8.8.255', $response->details[1]->value);
        static::assertSame('8.8.8.0/24', $response->details[2]->value);
        static::assertSame('US', $response->details[5]->value);
        static::assertSame(['active'], $response->statuses);
    }

    public function testParsesWhoisText(): void
    {
        $response = new WhoisParser()->parse(
            query: 'example.com',
            kind: 'domain',
            protocol: 'whois',
            server: 'whois.example.test',
            raw: <<<'WHOIS'
                Domain Name: EXAMPLE.COM
                Registrar: Example Registrar
                Creation Date: 2020-01-01T00:00:00Z
                Registry Expiry Date: 2030-01-01T00:00:00Z
                Name Server: NS1.EXAMPLE.COM
                Name Server: NS2.EXAMPLE.COM
                Domain Status: clientTransferProhibited https://icann.org/epp#clientTransferProhibited
                WHOIS,
        );

        static::assertSame('whois', $response->protocol);
        static::assertSame('EXAMPLE.COM', $response->details[0]->value);
        static::assertSame('Example Registrar', $response->details[1]->value);
        static::assertSame(['ns1.example.com', 'ns2.example.com'], $response->nameservers);
        static::assertSame(['clientTransferProhibited'], $response->statuses);
        static::assertCount(2, $response->events);
    }

    public function testParsesRuWhoisText(): void
    {
        $response = new WhoisParser()->parse(
            query: 'example.ru',
            kind: 'domain',
            protocol: 'whois',
            server: 'whois.tcinet.ru',
            raw: <<<'WHOIS'
                domain:        EXAMPLE.RU
                state:         REGISTERED, NOT DELEGATED, VERIFIED
                person:        Private Person
                registrar:     RU-CENTER-RU
                created:       2000-04-20T13:08:22Z
                paid-till:     2027-04-30T21:00:00Z
                nserver:       ns1.example.ru.
                nserver:       ns2.example.ru.
                WHOIS,
        );

        $values = \array_map(static fn ($field): string => $field->value, $response->details);

        static::assertContains('EXAMPLE.RU', $values);
        static::assertContains('Private Person', $values);
        static::assertContains('RU-CENTER-RU', $values);
        static::assertContains('REGISTERED', $response->statuses);
        static::assertSame(['ns1.example.ru.', 'ns2.example.ru.'], $response->nameservers);
        static::assertCount(2, $response->events);
    }
}

