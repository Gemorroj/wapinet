<?php

declare(strict_types=1);

namespace App\Service\Api\Validator;

use App\Dto\Api\Validator\ValidatorResponse;
use CSSValidator\Options\ProfileType;
use CSSValidator\Options\WarningType;

interface ValidatorRunnerInterface
{
    public function validatePhp(?string $code = null, ?string $url = null): ValidatorResponse;

    public function validateHtml(?string $code = null, ?string $url = null): ValidatorResponse;

    public function validateCss(
        ?string $code = null,
        ?string $url = null,
        ?ProfileType $profile = null,
        ?WarningType $warnings = null,
    ): ValidatorResponse;
}


