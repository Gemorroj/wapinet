<?php

namespace App\Twig\Extension\Ginfo;

use Ginfo\Common;
use Ginfo\Info\Service;

class SearchService extends \Twig_Extension
{
    public function getFilters(): array
    {
        return [
            new \Twig_SimpleFilter('wapinet_ginfo_search_service', [$this, 'searchService']),
        ];
    }

    /**
     * @param Service[] $services
     * @param string    $serviceName
     *
     * @return Service|null
     */
    public function searchService(array $services, string $serviceName): ?Service
    {
        return Common::searchService($services, $serviceName);
    }
}
