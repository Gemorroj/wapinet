<?php

declare(strict_types=1);

namespace App\Dto\Api\Validator;

use CSSValidator\Options\ProfileType;
use CSSValidator\Options\WarningType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CssValidatorRequest
{
    public function __construct(
        #[Assert\Length(max: ValidatorRequest::MAX_CODE_SIZE)]
        public ?string $code = null,
        #[Assert\Length(max: 2048)]
        #[Assert\Url(protocols: ['http', 'https'], requireTld: false)]
        public ?string $url = null,
        public ?ProfileType $profile = null,
        public ?WarningType $warnings = null,
    ) {
    }
}

