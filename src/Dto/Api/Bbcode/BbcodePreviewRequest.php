<?php

declare(strict_types=1);

namespace App\Dto\Api\Bbcode;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BbcodePreviewRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100000)]
        public string $text = '',
    ) {
    }
}
