<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\Api\ProblemFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final readonly class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private ProblemFactory $problems,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return $this->problems->response(403, 'Доступ запрещён.');
    }
}
