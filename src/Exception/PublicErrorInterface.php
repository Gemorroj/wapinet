<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * An exception whose message is safe to expose to API clients.
 */
interface PublicErrorInterface
{
    public function getPublicMessage(): string;
}
