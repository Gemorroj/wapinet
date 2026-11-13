<?php

namespace App\Security;

use App\Dto\Api\Auth\TokenResponse;
use App\Dto\Api\User\UserResponse;
use App\Entity\ApiToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ApiLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ClockInterface $clock,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('The authenticated user must be an instance of '.User::class.'.');
        }

        $plainToken = ApiToken::generatePlainToken();
        $apiToken = new ApiToken(
            $user,
            ApiToken::hash($plainToken),
            $this->deviceName($request),
            $this->clock->now()->modify('+30 days'),
        );

        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        $payload = $this->serializer->serialize(
            new TokenResponse($plainToken, $apiToken->getExpiresAt(), UserResponse::fromUser($user)),
            'json',
            ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS],
        );

        return new JsonResponse($payload, Response::HTTP_OK, [], true);
    }

    private function deviceName(Request $request): string
    {
        $deviceName = $request->getPayload()->get('deviceName');

        if (!\is_string($deviceName) || '' === \trim($deviceName)) {
            return 'api';
        }

        return \mb_substr(\trim($deviceName), 0, 100);
    }
}
