<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Entity\User;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class UserSearch
{
    public function __construct(
        private SearchEngineInterface $engine,
        private SearchPager $searchPager,
        #[Autowire('%wapinet_paginate_maxperpage%')]
        private int $perPage,
    ) {
    }

    /**
     * @return Pagerfanta<User>
     */
    public function search(string $text, int $page): Pagerfanta
    {
        $result = $this->engine->search(new SearchRequest(
            'users',
            $text,
            [],
            [['_score' => 'desc'], ['id' => 'desc']],
            $page,
            $this->perPage,
        ));

        return $this->searchPager->paginate(User::class, $result, $page, $this->perPage);
    }
}
