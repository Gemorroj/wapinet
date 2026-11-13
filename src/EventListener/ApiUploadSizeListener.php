<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\ByteSize;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\ContentTooLargeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * PHP silently drops $_POST and $_FILES when the body exceeds post_max_size.
 * Without this check every upload endpoint reports a misleading validation
 * error ("no file provided") instead of a readable 413.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 100, method: 'onRequest')]
final class ApiUploadSizeListener
{
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!\str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if (!\in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            return;
        }

        $limit = self::iniBytes((string) \ini_get('post_max_size'));
        $length = (int) ($request->headers->get('content-length') ?? 0);

        if ($limit > 0 && $length > $limit) {
            throw new ContentTooLargeHttpException(\sprintf('Файл слишком большой: за один запрос можно загрузить не более %s.', ByteSize::format($limit)));
        }
    }

    private static function iniBytes(string $value): int
    {
        $value = \trim($value);

        if ('' === $value) {
            return 0;
        }

        $unit = \strtoupper(\substr($value, -1));

        return (int) ((float) $value * match ($unit) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        });
    }
}
