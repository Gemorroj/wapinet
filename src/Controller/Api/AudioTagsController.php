<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\AudioTags\AudioTagsCreateRequest;
use App\Dto\Api\AudioTags\AudioTagsInfoResponse;
use App\Dto\Api\AudioTags\AudioTagsUpdateRequest;
use App\Service\Api\AudioTags\AudioTagsException;
use App\Service\Api\AudioTags\AudioTagsManager;
use App\Service\Api\AudioTags\AudioTagsNotFoundException;
use App\Service\Api\AudioTags\AudioTagsStorage;
use App\Service\Api\RateLimit;
use App\Service\Translit;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/audio-tags', name: 'api_audio_tags_')]
#[OA\Tag(name: 'Audio tags')]
final class AudioTagsController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: AudioTagsCreateRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'Uploaded audio file and its tags',
        content: new Model(type: AudioTagsInfoResponse::class),
    )]
    #[OA\Response(response: 422, description: 'Invalid audio file, unsupported link or validation error')]
    #[OA\Response(response: 429, description: 'Too many audio tag requests')]
    public function create(
        #[MapRequestPayload(mapWhenEmpty: true)]
        AudioTagsCreateRequest $payload,
        Request $request,
        AudioTagsManager $manager,
        #[Autowire(service: 'limiter.api_audio_tags')]
        RateLimiterFactory $limiter,
    ): JsonResponse {
        RateLimit::consumeForIp($limiter, $request);

        try {
            return $this->json($manager->create($payload), Response::HTTP_CREATED);
        } catch (AudioTagsException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Audio file information and tags',
        content: new Model(type: AudioTagsInfoResponse::class),
    )]
    #[OA\Response(response: 404, description: 'File not found or expired')]
    public function show(string $id, AudioTagsManager $manager): JsonResponse
    {
        try {
            return $this->json($manager->info($id));
        } catch (AudioTagsNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '[a-f0-9]{32}'], methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: AudioTagsUpdateRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Updated audio file information and tags',
        content: new Model(type: AudioTagsInfoResponse::class),
    )]
    #[OA\Response(response: 404, description: 'File not found or expired')]
    #[OA\Response(response: 422, description: 'Unsupported format or validation error')]
    #[OA\Response(response: 429, description: 'Too many audio tag requests')]
    public function update(
        string $id,
        #[MapRequestPayload(mapWhenEmpty: true)]
        AudioTagsUpdateRequest $payload,
        Request $request,
        AudioTagsManager $manager,
        #[Autowire(service: 'limiter.api_audio_tags')]
        RateLimiterFactory $limiter,
    ): JsonResponse {
        RateLimit::consumeForIp($limiter, $request);

        try {
            return $this->json($manager->update($id, $payload));
        } catch (AudioTagsNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (AudioTagsException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }
    }

    #[Route('/{id}/clear', name: 'clear', requirements: ['id' => '[a-f0-9]{32}'], methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Audio file information after clearing all tags',
        content: new Model(type: AudioTagsInfoResponse::class),
    )]
    #[OA\Response(response: 404, description: 'File not found or expired')]
    #[OA\Response(response: 422, description: 'Unsupported format')]
    #[OA\Response(response: 429, description: 'Too many audio tag requests')]
    public function clear(
        string $id,
        Request $request,
        AudioTagsManager $manager,
        #[Autowire(service: 'limiter.api_audio_tags')]
        RateLimiterFactory $limiter,
    ): JsonResponse {
        RateLimit::consumeForIp($limiter, $request);

        try {
            return $this->json($manager->clearTags($id));
        } catch (AudioTagsNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (AudioTagsException $exception) {
            throw new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }
    }

    #[Route('/{id}/download', name: 'download', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Audio file download',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string', format: 'binary'),
        ),
    )]
    #[OA\Response(response: 404, description: 'File not found or expired')]
    public function download(
        string $id,
        AudioTagsStorage $storage,
        Translit $translit,
    ): BinaryFileResponse {
        try {
            $path = $storage->path($id);
        } catch (AudioTagsNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        $originalName = $storage->originalName($id);
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $originalName,
            $translit->toAscii($originalName),
        );
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }

    #[Route('/{id}/cover', name: 'cover', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Embedded cover art',
        content: new OA\MediaType(mediaType: 'image/*'),
    )]
    #[OA\Response(response: 404, description: 'Cover not found')]
    public function cover(string $id, AudioTagsManager $manager): Response
    {
        try {
            $cover = $manager->cover($id);
        } catch (AudioTagsNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return new Response($cover['data'], Response::HTTP_OK, [
            'Content-Type' => $cover['mime'],
            'Cache-Control' => 'no-store',
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '[a-f0-9]{32}'], methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'Temporary file removed')]
    public function delete(string $id, AudioTagsManager $manager): Response
    {
        $manager->delete($id);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
