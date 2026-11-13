<?php

declare(strict_types=1);

namespace App\Service\Api\Whois;

use App\Dto\Api\Network\IpInsightResponse;
use App\Dto\Api\Whois\WhoisContact;
use App\Dto\Api\Whois\WhoisDnsInfo;
use App\Dto\Api\Whois\WhoisEvent;
use App\Dto\Api\Whois\WhoisField;
use App\Dto\Api\Whois\WhoisResponse;
use WhoRdap\QueryType;

final class WhoisParser
{
    private const array EVENT_LABELS = [
        'registration' => 'Зарегистрирован',
        'expiration' => 'Истекает',
        'last changed' => 'Изменён',
        'last update of RDAP database' => 'Обновлён в RDAP',
        'registrar expiration' => 'Истекает у регистратора',
        'transfer' => 'Передан',
        'locked' => 'Заблокирован',
        'unlocked' => 'Разблокирован',
    ];

    private const array WHOIS_LABELS = [
        'domain name' => 'Домен',
        'domain' => 'Домен',
        'registrar' => 'Регистратор',
        'creation date' => 'Зарегистрирован',
        'created' => 'Зарегистрирован',
        'registry expiry date' => 'Истекает',
        'paid-till' => 'Оплачен до',
        'updated date' => 'Изменён',
        'last updated' => 'Изменён',
        'registrant organization' => 'Организация',
        'registrant country' => 'Страна',
        'person' => 'Владелец',
        'org' => 'Организация',
        'state' => 'Состояние',
        'admin-contact' => 'Контакт администратора',
        'free-date' => 'Освобождается',
        'source' => 'Источник',
        'descr' => 'Описание',
        'netrange' => 'Диапазон',
        'cidr' => 'CIDR',
        'netname' => 'Имя сети',
        'orgname' => 'Организация',
        'organization' => 'Организация',
        'country' => 'Страна',
        'asnumber' => 'ASN',
        'asname' => 'Название ASN',
    ];

    public function kind(string $query): string
    {
        return QueryType::detect($query)->value;
    }

    public function parse(
        string $query,
        string $kind,
        string $protocol,
        string $server,
        string $raw,
        ?WhoisDnsInfo $dns = null,
        ?IpInsightResponse $insight = null,
    ): WhoisResponse {
        $details = [];
        $statuses = [];
        $events = [];
        $nameservers = [];
        $contacts = [];

        if ('rdap' === $protocol) {
            $data = \json_decode($raw, true);

            if (\is_array($data)) {
                $this->parseRdap($data, $kind, $details, $statuses, $events, $nameservers, $contacts);
            }
        } else {
            $this->parseWhois($raw, $details, $statuses, $events, $nameservers);
        }

        return new WhoisResponse(
            query: $query,
            kind: $kind,
            protocol: $protocol,
            server: $server,
            raw: $raw,
            details: $details,
            statuses: $statuses,
            events: $events,
            nameservers: $nameservers,
            contacts: $contacts,
            dns: $dns ?? WhoisDnsInfo::empty(),
            insight: $insight,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param list<WhoisField>     $details
     * @param list<string>         $statuses
     * @param list<WhoisEvent>     $events
     * @param list<string>         $nameservers
     * @param list<WhoisContact>   $contacts
     */
    private function parseRdap(
        array $data,
        string $kind,
        array &$details,
        array &$statuses,
        array &$events,
        array &$nameservers,
        array &$contacts,
    ): void {
        if ('domain' === $kind) {
            $this->add($details, 'Домен', $this->string($data['unicodeName'] ?? $data['ldhName'] ?? null));
            $this->add($details, 'Handle', $this->string($data['handle'] ?? null));

            if (isset($data['secureDNS']['delegationSigned'])) {
                $this->add($details, 'DNSSEC', $data['secureDNS']['delegationSigned'] ? 'Подписан' : 'Не подписан');
            }
        } elseif ('asn' === $kind) {
            $this->add($details, 'ASN', $this->string($data['handle'] ?? null));
            $this->add($details, 'Диапазон', $this->range($data['startAutnum'] ?? null, $data['endAutnum'] ?? null));
            $this->add($details, 'Название', $this->string($data['name'] ?? null));
            $this->add($details, 'Страна', $this->string($data['country'] ?? null));
        } else {
            $this->add($details, 'Имя сети', $this->string($data['name'] ?? null));
            $this->add($details, 'Диапазон', $this->range($data['startAddress'] ?? null, $data['endAddress'] ?? null));
            $this->add($details, 'CIDR', $this->cidrs($data['cidr0_cidrs'] ?? null));
            $this->add($details, 'Версия IP', $this->string($data['ipVersion'] ?? null));
            $this->add($details, 'Тип', $this->string($data['type'] ?? null));
            $this->add($details, 'Страна', $this->string($data['country'] ?? null));
            $this->add($details, 'Handle', $this->string($data['handle'] ?? null));
            $this->add($details, 'Родительская сеть', $this->string($data['parentHandle'] ?? null));
        }

        foreach ($data['status'] ?? [] as $status) {
            $status = $this->string($status);

            if (null !== $status) {
                $statuses[] = $status;
            }
        }

        foreach ($data['events'] ?? [] as $event) {
            if (!\is_array($event)) {
                continue;
            }

            $action = $this->string($event['eventAction'] ?? null);
            $date = $this->string($event['eventDate'] ?? null);

            if (null !== $action && null !== $date) {
                $events[] = new WhoisEvent(self::EVENT_LABELS[$action] ?? $action, $date);
            }
        }

        foreach ($data['nameservers'] ?? [] as $nameserver) {
            if (!\is_array($nameserver)) {
                continue;
            }

            $name = $this->string($nameserver['ldhName'] ?? $nameserver['unicodeName'] ?? null);

            if (null !== $name) {
                $nameservers[] = \strtolower($name);
            }
        }

        $contacts = $this->contacts($data['entities'] ?? []);
        $nameservers = \array_values(\array_unique($nameservers));
    }

    /**
     * @param list<WhoisField> $details
     * @param list<string>     $statuses
     * @param list<WhoisEvent> $events
     * @param list<string>     $nameservers
     */
    private function parseWhois(
        string $raw,
        array &$details,
        array &$statuses,
        array &$events,
        array &$nameservers,
    ): void {
        $found = [];

        foreach (\preg_split('/\R/', $raw) ?: [] as $line) {
            if (1 !== \preg_match('/^\s*([^:]{1,60}):\s*(.+?)\s*$/', $line, $matches)) {
                continue;
            }

            $key = \mb_strtolower(\trim($matches[1]));
            $value = \trim($matches[2]);

            if ('' === $value) {
                continue;
            }

            if (\in_array($key, ['name server', 'nserver'], true)) {
                $nameservers[] = \strtolower($value);

                continue;
            }

            if ('domain status' === $key) {
                $statuses[] = \explode(' ', $value)[0];

                continue;
            }

            if ('state' === $key) {
                foreach (\explode(',', $value) as $status) {
                    $status = \trim($status);

                    if ('' !== $status) {
                        $statuses[] = $status;
                    }
                }
            }

            if (isset(self::WHOIS_LABELS[$key]) && !isset($found[$key])) {
                $found[$key] = true;
                $details[] = new WhoisField(self::WHOIS_LABELS[$key], $value);

                if (\in_array($key, ['creation date', 'created', 'registry expiry date', 'paid-till', 'updated date', 'last updated'], true)) {
                    $events[] = new WhoisEvent(self::WHOIS_LABELS[$key], $value);
                }
            }
        }

        $statuses = \array_values(\array_unique($statuses));
        $nameservers = \array_values(\array_unique($nameservers));
    }

    /**
     * @param list<mixed> $entities
     *
     * @return list<WhoisContact>
     */
    private function contacts(array $entities): array
    {
        $contacts = [];

        foreach ($entities as $entity) {
            if (!\is_array($entity)) {
                continue;
            }

            $vcard = $this->vcard($entity['vcardArray'] ?? []);

            foreach ($entity['roles'] ?? [] as $role) {
                $role = $this->string($role);

                if (null === $role || !\in_array($role, ['registrar', 'registrant', 'administrative', 'technical', 'abuse', 'billing'], true)) {
                    continue;
                }

                $contact = new WhoisContact(
                    role: $role,
                    name: $vcard['fn'] ?? null,
                    organization: $vcard['org'] ?? null,
                    email: $vcard['email'] ?? null,
                );

                $contacts[$role.($contact->email ?? $contact->name ?? $contact->organization ?? '')] = $contact;
            }

            foreach ($this->contacts($entity['entities'] ?? []) as $contact) {
                $contacts[$contact->role.($contact->email ?? $contact->name ?? '')] = $contact;
            }
        }

        return \array_values($contacts);
    }

    /**
     * @param array<mixed> $vcardArray
     *
     * @return array<string, string>
     */
    private function vcard(array $vcardArray): array
    {
        $properties = [];
        $items = $vcardArray[1] ?? null;

        if (!\is_array($items)) {
            return $properties;
        }

        foreach ($items as $item) {
            if (!\is_array($item) || \count($item) < 4) {
                continue;
            }

            $name = $this->string($item[0] ?? null);
            $value = $item[3] ?? null;

            if (null === $name) {
                continue;
            }

            if (\is_array($value)) {
                $value = \implode(' ', \array_filter($value, '\is_string'));
            }

            $value = $this->string($value);

            if (null !== $value) {
                $properties[$name] = $value;
            }
        }

        return $properties;
    }

    /**
     * @param list<WhoisField> $details
     */
    private function add(array &$details, string $label, ?string $value): void
    {
        if (null !== $value && '' !== $value) {
            $details[] = new WhoisField($label, $value);
        }
    }

    private function range(mixed $start, mixed $end): ?string
    {
        $start = $this->string($start);
        $end = $this->string($end);

        if (null === $start) {
            return null;
        }

        return null !== $end && $end !== $start ? $start.' — '.$end : $start;
    }

    /**
     * @param array<mixed>|null $cidrs
     */
    private function cidrs(?array $cidrs): ?string
    {
        if (null === $cidrs) {
            return null;
        }

        $values = [];

        foreach ($cidrs as $cidr) {
            if (!\is_array($cidr)) {
                continue;
            }

            $prefix = $this->string($cidr['v4prefix'] ?? $cidr['v6prefix'] ?? null);
            $length = $cidr['length'] ?? null;

            if (null !== $prefix && null !== $length) {
                $values[] = $prefix.'/'.$length;
            }
        }

        return [] === $values ? null : \implode(', ', $values);
    }

    private function string(mixed $value): ?string
    {
        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        if (!\is_string($value) || '' === \trim($value)) {
            return null;
        }

        return \trim($value);
    }
}


