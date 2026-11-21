<?php

namespace App\Dto\Api\Config;

final readonly class ConfigResponse
{
    /**
     * @param list<CountryResponse>  $countries
     * @param list<TimezoneResponse> $timezones
     */
    public function __construct(
        public CaptchaConfigResponse $captcha,
        public array $countries,
        public array $timezones,
    ) {
    }
}
