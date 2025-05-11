<?php

namespace App\Twig\Extension\Ginfo;

use Ginfo\Info\Service;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SearchService extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_ginfo_search_service', $this->searchService(...)),
        ];
    }

    /**
     * @param Service[] $services
     */
    public function searchService(array $services, string $serviceName): ?Service
    {
        foreach ($services as $service) {
            if ($service->getName() === $serviceName) {
                return $service;
            }
        }

        return null;
    }
}
