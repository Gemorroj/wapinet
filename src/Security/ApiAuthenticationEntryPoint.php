<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Api\ProblemFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final readonly class ApiAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ProblemFactory $problems,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->problems->response(401, 'Требуется авторизация.', ['WWW-Authenticate' => 'Bearer']);
    }
}
