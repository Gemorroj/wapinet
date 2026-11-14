<?php

declare(strict_types=1);

namespace App\Dto\Api\Whois;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class WhoisRequest
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public string $query = '',
        #[Assert\Choice(choices: ['auto', 'rdap', 'whois'])]
        public string $protocol = 'auto',
    ) {
    }
}
