<?php

namespace App\Twig\Extension\Ginfo;

use Ginfo\Common;
use Ginfo\Info\Service;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SearchService extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_ginfo_search_service', [$this, 'searchService']),
        ];
    }

    /**
     * @param Service[] $services
     */
    public function searchService(array $services, string $serviceName): ?Service
    {
        return Common::searchService($services, $serviceName);
    }
}
