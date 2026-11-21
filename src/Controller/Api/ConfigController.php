<?php

namespace App\Controller\Api;

use App\Dto\Api\Config\CaptchaConfigResponse;
use App\Dto\Api\Config\ConfigResponse;
use App\Dto\Api\Config\CountryResponse;
use App\Dto\Api\Config\TimezoneResponse;
use App\Service\Captcha\CaptchaVerifierInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/config', name: 'api_config', methods: ['GET'])]
#[OA\Tag(name: 'System')]
final class ConfigController
{
    #[OA\Response(
        response: 200,
        description: 'Public client configuration',
        content: new Model(type: ConfigResponse::class),
    )]
    #[Cache(public: true, maxage: 300)]
    public function __invoke(CaptchaVerifierInterface $captchaVerifier): JsonResponse
    {
        return new JsonResponse(new ConfigResponse(
            new CaptchaConfigResponse(
                $captchaVerifier->isEnabled(),
                $captchaVerifier->isEnabled() ? $captchaVerifier->getSiteKey() : '',
            ),
            $this->countries(),
            $this->timezones(),
        ));
    }

    /**
     * @return list<CountryResponse>
     */
    private function countries(): array
    {
        $countries = [];

        foreach (Countries::getNames('ru') as $code => $name) {
            $countries[] = new CountryResponse($code, $name);
        }

        return $countries;
    }

    /**
     * @return list<TimezoneResponse>
     */
    private function timezones(): array
    {
        $timezones = [];

        foreach (Timezones::getNames('ru') as $id => $name) {
            $timezones[] = new TimezoneResponse($id, $name);
        }

        return $timezones;
    }
}
