<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Archiver\ArchiveResponse;
use App\Dto\Api\Archiver\ArchiveSourceRequest;
use App\Service\Api\Archiver\Archiver;
use App\Service\Api\Archiver\ArchiveSourceResolver;
use App\Service\Api\RateLimit;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/archiver', name: 'api_archiver_')]
final class ArchiverController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: ArchiveSourceRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Archive with the uploaded file',
        content: new Model(type: ArchiveResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Invalid file, unsupported link or limit exceeded')]
    #[OA\Response(response: 429, description: 'Too many archiver requests')]
    #[OA\Response(response: 503, description: 'Not enough free disk space')]
    public function create(
        #[MapRequestPayload(mapWhenEmpty: true)]
        ArchiveSourceRequest $payload,
        Request $request,
        Archiver $archiver,
        ArchiveSourceResolver $resolver,
        #[Autowire(service: 'limiter.api_archiver_write')]
        RateLimiterFactory $archiverLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($archiverLimiter, $request);

        return $this->json($archiver->create($resolver->resolve($payload)));
    }

    #[Route('/extract', name: 'extract', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: ArchiveSourceRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Archive with extracted files',
        content: new Model(type: ArchiveResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Unsupported archive, unsafe paths or limit exceeded')]
    #[OA\Response(response: 429, description: 'Too many archiver requests')]
    #[OA\Response(response: 503, description: 'Not enough free disk space')]
    public function extract(
        #[MapRequestPayload(mapWhenEmpty: true)]
        ArchiveSourceRequest $payload,
        Request $request,
        Archiver $archiver,
        ArchiveSourceResolver $resolver,
        #[Autowire(service: 'limiter.api_archiver_write')]
        RateLimiterFactory $archiverLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($archiverLimiter, $request);

        $sources = $resolver->resolve($payload);

        if (1 !== \count($sources)) {
            throw new UnprocessableEntityHttpException('Для распаковки выберите один архив.');
        }

        return $this->json($archiver->extract($sources[0]));
    }

    #[Route('/{archive}', name: 'show', requirements: ['archive' => 'archive[a-z0-9]{8,32}'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Archive contents',
        content: new Model(type: ArchiveResponse::class),
    )]
    #[OA\Response(response: 404, description: 'Archive not found or expired')]
    public function show(string $archive, Archiver $archiver): JsonResponse
    {
        return $this->json($archiver->response($archive));
    }

    #[Route('/{archive}', name: 'remove', requirements: ['archive' => 'archive[a-z0-9]{8,32}'], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Archive deleted')]
    #[OA\Response(response: 404, description: 'Archive not found or expired')]
    public function removeArchive(string $archive, Archiver $archiver): Response
    {
        $archiver->delete($archive);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{archive}/files', name: 'add', requirements: ['archive' => 'archive[a-z0-9]{8,32}'], methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: ArchiveSourceRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Archive contents after adding the file',
        content: new Model(type: ArchiveResponse::class),
    )]
    #[OA\Response(response: 404, description: 'Archive not found or expired')]
    #[OA\Response(response: 422, description: 'Invalid file or limit exceeded')]
    #[OA\Response(response: 429, description: 'Too many archiver requests')]
    #[OA\Response(response: 503, description: 'Not enough free disk space')]
    public function add(
        string $archive,
        #[MapRequestPayload(mapWhenEmpty: true)]
        ArchiveSourceRequest $payload,
        Request $request,
        Archiver $archiver,
        ArchiveSourceResolver $resolver,
        #[Autowire(service: 'limiter.api_archiver_write')]
        RateLimiterFactory $archiverLimiter,
    ): JsonResponse {
        RateLimit::consumeForIp($archiverLimiter, $request);

        return $this->json($archiver->add($archive, $resolver->resolve($payload)));
    }

    #[Route('/{archive}/files', name: 'delete', requirements: ['archive' => 'archive[a-z0-9]{8,32}'], methods: ['DELETE'])]
    #[OA\Parameter(name: 'path', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'Archive contents after removing the entry',
        content: new Model(type: ArchiveResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Unsafe path')]
    #[OA\Response(response: 404, description: 'Archive or entry not found')]
    public function delete(string $archive, Request $request, Archiver $archiver): JsonResponse
    {
        $path = $request->query->get('path');

        if (!\is_string($path) || '' === $path) {
            throw new UnprocessableEntityHttpException('Не указан файл для удаления.');
        }

        return $this->json($archiver->remove($archive, $path));
    }

    #[Route('/{archive}/download', name: 'download', requirements: ['archive' => 'archive[a-z0-9]{8,32}'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'ZIP archive with all files',
        content: new OA\MediaType(
            mediaType: 'application/zip',
            schema: new OA\Schema(type: 'string', format: 'binary'),
        ),
    )]
    #[OA\Response(response: 404, description: 'Archive not found or expired')]
    public function download(
        string $archive,
        Request $request,
        Archiver $archiver,
        #[Autowire(service: 'limiter.api_archiver_download')]
        RateLimiterFactory $downloadLimiter,
    ): BinaryFileResponse {
        RateLimit::consumeForIp($downloadLimiter, $request);

        $response = new BinaryFileResponse($archiver->zip($archive));
        $response->deleteFileAfterSend(true);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $archive.'.zip');
        $response->headers->set('Content-Type', 'application/zip');

        return $response;
    }

    #[Route('/{archive}/file', name: 'file', requirements: ['archive' => 'archive[a-z0-9]{8,32}'], methods: ['GET'])]
    #[OA\Parameter(name: 'path', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(
        response: 200,
        description: 'One file from the archive',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string', format: 'binary'),
        ),
    )]
    #[OA\Response(response: 403, description: 'Unsafe path')]
    #[OA\Response(response: 404, description: 'Archive or entry not found')]
    public function downloadFile(string $archive, Request $request, Archiver $archiver): BinaryFileResponse
    {
        $path = $request->query->get('path');

        if (!\is_string($path) || '' === $path) {
            throw new UnprocessableEntityHttpException('Не указан файл для скачивания.');
        }

        $response = new BinaryFileResponse($archiver->filePath($archive, $path));
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            \basename(\str_replace('\\', '/', $path)),
        );
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }
}
