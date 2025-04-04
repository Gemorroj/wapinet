<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use WhoRdap\WhoRdap as WhoRdapCommon;
use WhoRdap\NetworkClient\NetworkClient as WhoRdapNetworkClient;

final readonly class WhoRdap
{
    public function getWhoRdap(): WhoRdapCommon
    {
        $cache = new FilesystemAdapter('whordap', 86400 * 7);
        $networkClient = new WhoRdapNetworkClient(cache: $cache);

        return new WhoRdapCommon($networkClient);
    }
}
