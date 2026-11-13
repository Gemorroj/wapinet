<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class ApiLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $tooManyAttempts = $exception instanceof TooManyLoginAttemptsAuthenticationException;
        $status = $tooManyAttempts ? Response::HTTP_TOO_MANY_REQUESTS : Response::HTTP_UNAUTHORIZED;

        return new JsonResponse([
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'status' => $status,
            'detail' => $tooManyAttempts ? 'Too many login attempts.' : 'Invalid credentials.',
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
