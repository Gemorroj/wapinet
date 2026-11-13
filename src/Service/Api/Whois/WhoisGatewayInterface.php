<?php

declare(strict_types=1);

namespace App\Service\Api\Whois;

interface WhoisGatewayInterface
{
    /**
     * @return array{protocol: string, server: string, response: string}
     */
    public function lookup(string $query, string $protocol): array;
}

