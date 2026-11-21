<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\BrowserInfo\BrowserInfoResponse;
use App\Service\Api\BrowserInfo\BrowserInfoPresenter;
use App\Service\Network\IpAddress;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/browser-info', name: 'api_browser_info_')]
final class BrowserInfoController extends AbstractController
{
    private const int MAX_WEBRTC_IPS = 5;

    #[Route('', name: 'show', methods: ['GET'])]
    #[OA\Parameter(
        name: 'webrtc',
        description: 'Comma-separated IP addresses discovered through WebRTC',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Information the server can see about the client request',
        content: new Model(type: BrowserInfoResponse::class),
    )]
    public function show(Request $request, BrowserInfoPresenter $presenter): JsonResponse
    {
        return $this->json($presenter->toResponse($request, $this->webrtcIps($request)));
    }

    /**
     * @return list<string>
     */
    private function webrtcIps(Request $request): array
    {
        $value = $request->query->get('webrtc');

        if (!\is_string($value) || '' === $value) {
            return [];
        }

        $ips = [];

        foreach (\explode(',', $value) as $ip) {
            $ip = \trim($ip);

            if (!IpAddress::isValid($ip) || \in_array($ip, $ips, true)) {
                continue;
            }

            $ips[] = $ip;

            if (\count($ips) >= self::MAX_WEBRTC_IPS) {
                break;
            }
        }

        return $ips;
    }
}
