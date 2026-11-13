<?php

declare(strict_types=1);

namespace App\Dto\Api\Bbcode;

final readonly class BbcodePreviewResponse
{
    public function __construct(public string $html)
    {
    }
}
