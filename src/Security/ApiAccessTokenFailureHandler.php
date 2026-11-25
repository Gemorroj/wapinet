<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Api\ProblemFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class ApiAccessTokenFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private ProblemFactory $problems,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->problems->response(401, 'Недействительный токен.', ['WWW-Authenticate' => 'Bearer error="invalid_token"']);
    }
}
