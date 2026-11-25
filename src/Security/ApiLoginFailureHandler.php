<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Api\ProblemFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class ApiLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private ProblemFactory $problems,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $tooManyAttempts = $exception instanceof TooManyLoginAttemptsAuthenticationException;

        return $this->problems->response(
            $tooManyAttempts ? Response::HTTP_TOO_MANY_REQUESTS : Response::HTTP_UNAUTHORIZED,
            $tooManyAttempts ? 'Слишком много попыток входа. Попробуйте позже.' : 'Неверный логин или пароль.',
        );
    }
}
