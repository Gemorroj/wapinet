<?php

namespace App\Twig\Extension\Ginfo;

use Ginfo\Common;
use Ginfo\Info\Service;

class SearchService extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('wapinet_ginfo_search_service', [$this, 'searchService']),
        ];
    }

    /**
     * @param Service[] $services
     * @param string $serviceName
     * @return \Ginfo\Info\Service|null
     */
    public function searchService(array $services, string $serviceName)
    {
        return Common::searchService($services, $serviceName);
    }

    public function getName()
    {
        return 'wapinet_ginfo_search_service';
    }
}
