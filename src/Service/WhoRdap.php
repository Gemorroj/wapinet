<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use WhoRdap\NetworkClient\NetworkClient as WhoRdapNetworkClient;
use WhoRdap\WhoRdap as WhoRdapCommon;

final readonly class WhoRdap
{
    public function getWhoRdap(): WhoRdapCommon
    {
        $cache = new FilesystemAdapter('whordap', 86400);
        $networkClient = new WhoRdapNetworkClient(cache: $cache);

        return new WhoRdapCommon($networkClient);
    }
}
