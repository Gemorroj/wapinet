<?php

namespace App\Controller\Api;

use App\Dto\Api\File\ArchiveListResponse;
use App\Dto\Api\File\FileCreateRequest;
use App\Dto\Api\File\FileListQuery;
use App\Dto\Api\File\FileListResponse;
use App\Dto\Api\File\FileResponse;
use App\Dto\Api\File\FileStatisticsResponse;
use App\Dto\Api\File\FileStatisticsUser;
use App\Dto\Api\File\FileUpdateRequest;
use App\Entity\File;
use App\Entity\User;
use App\Enum\FileCategory;
use App\Repository\FileRepository;
use App\Security\FileVoter;
use App\Service\Api\File\FileAccess;
use App\Service\Api\File\FileArchive;
use App\Service\Api\File\FilePresenter;
use App\Service\Api\File\FileSearch;
use App\Service\Api\File\FileService;
use App\Service\Api\File\FileUploadGuard;
use App\Service\Api\File\FileViewTracker;
use App\Service\Paginate;
use App\Service\Search\SearchUnavailableException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/files', name: 'api_files_')]
#[OA\Tag(name: 'Files')]
final class FilesController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of files',
        content: new Model(type: FileListResponse::class),
    )]
    #[OA\Response(response: 503, description: 'Search is unavailable')]
    public function list(
        #[MapQueryString]
        FileListQuery $query,
        FileRepository $fileRepository,
        Paginate $paginate,
        FilePresenter $presenter,
        FileSearch $fileSearch,
    ): JsonResponse {
        $user = $this->getUser();

        if ($query->filter->mine && !$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Для просмотра своих файлов требуется авторизация.');
        }

        $viewer = $user instanceof User ? $user : null;

        if ($fileSearch->supports($query, $viewer)) {
            try {
                return $this->json($fileSearch->search($query));
            } catch (SearchUnavailableException $exception) {
                throw new ServiceUnavailableHttpException(null, 'Поиск временно недоступен.', $exception);
            }
        }

        $pager = $paginate->paginate(
            $fileRepository->getApiListQueryBuilder($query, $viewer),
            $query->page,
            $query->perPage,
        );

        $files = [];
        foreach ($pager->getCurrentPageResults() as $file) {
            $files[] = $file;
        }

        return $this->json(new FileListResponse(
            \array_map($presenter->toListItem(...), $files),
            $pager->getNbResults(),
            $pager->getCurrentPage(),
            $pager->getMaxPerPage(),
        ));
    }

    #[Route('/statistics', name: 'statistics', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'File statistics',
        content: new Model(type: FileStatisticsResponse::class),
    )]
    public function statistics(FileRepository $fileRepository): JsonResponse
    {
        $statistic = $fileRepository->getStatistic();

        $categories = [];
        foreach (FileCategory::cases() as $category) {
            $categories[$category->value] = $fileRepository->countCategory($category->value);
        }

        return $this->json(new FileStatisticsResponse(
            (int) $statistic['count_files'],
            (int) $statistic['size_files'],
            (int) $statistic['count_views'],
            \array_map(
                static fn (array $user): FileStatisticsUser => new FileStatisticsUser(
                    (string) $user['username'],
                    (int) $user['uploads'],
                ),
                $statistic['users'],
            ),
            $categories,
        ));
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'File details',
        content: new Model(type: FileResponse::class),
    )]
    #[OA\Response(response: 403, description: 'File password required or invalid')]
    #[OA\Response(response: 404, description: 'File not found')]
    public function show(
        #[MapEntity(id: 'id', message: 'Файл не найден.')]
        File $file,
        Request $request,
        FileAccess $fileAccess,
        FileViewTracker $viewTracker,
        FilePresenter $presenter,
    ): JsonResponse {
        $fileAccess->assertCanAccess($file, $request);

        $viewTracker->track($file);

        return $this->json($presenter->toResponse($file));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: FileCreateRequest::class)),
        ),
    )]
    #[OA\Response(
        response: 201,
        description: 'File uploaded',
        content: new Model(type: FileResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Captcha verification failed')]
    #[OA\Response(response: 409, description: 'A file with identical content already exists')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    #[OA\Response(response: 429, description: 'Too many uploads')]
    #[OA\Response(response: 503, description: 'Captcha service is unavailable')]
    public function create(
        #[MapRequestPayload(mapWhenEmpty: true)]
        FileCreateRequest $payload,
        Request $request,
        FileService $fileService,
        FilePresenter $presenter,
        FileUploadGuard $uploadGuard,
    ): JsonResponse {
        $user = $this->getUser();
        $user = $user instanceof User ? $user : null;

        $uploadGuard->assertCanUpload($request, $user, $payload->captchaToken);

        $file = $fileService->create($payload, $request, $user);

        return $this->json($presenter->toResponse($file), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[IsGranted(FileVoter::EDIT, subject: 'file')]
    #[OA\RequestBody(
        required: true,
        content: [
            new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(ref: new Model(type: FileUpdateRequest::class)),
            ),
            new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: FileUpdateRequest::class)),
            ),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Updated file',
        content: new Model(type: FileResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Access denied')]
    #[OA\Response(response: 409, description: 'A file with identical content already exists')]
    #[OA\Response(response: 422, description: 'Validation failed')]
    public function update(
        #[MapEntity(id: 'id', message: 'Файл не найден.')]
        File $file,
        #[MapRequestPayload(mapWhenEmpty: true)]
        FileUpdateRequest $payload,
        FileService $fileService,
        FilePresenter $presenter,
    ): JsonResponse {
        $fileService->update($file, $payload);

        return $this->json($presenter->toResponse($file));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted(FileVoter::DELETE, subject: 'file')]
    #[OA\Response(response: 204, description: 'File deleted')]
    #[OA\Response(response: 403, description: 'Access denied')]
    public function delete(#[MapEntity(id: 'id', message: 'Файл не найден.')] File $file, FileService $fileService): Response
    {
        $fileService->delete($file);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/download', name: 'download', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'File contents',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string', format: 'binary'),
        ),
    )]
    #[OA\Response(response: 403, description: 'File password required or invalid')]
    #[OA\Response(response: 404, description: 'File not found')]
    public function download(
        #[MapEntity(id: 'id', message: 'Файл не найден.')]
        File $file,
        Request $request,
        FileAccess $fileAccess,
    ): BinaryFileResponse {
        $fileAccess->assertCanAccess($file, $request);

        $path = $file->getFile()?->getRealPath();

        if (false === $path || null === $path || !\is_file($path)) {
            throw new NotFoundHttpException('Файл отсутствует на диске.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getOriginalFileName());
        $response->headers->set('Content-Type', '' !== $file->getMimeType() ? $file->getMimeType() : 'application/octet-stream');

        return $response;
    }

    #[Route('/{id}/archive', name: 'archive', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Archive entries',
        content: new Model(type: ArchiveListResponse::class),
    )]
    #[OA\Response(response: 403, description: 'Archive password required or invalid')]
    #[OA\Response(response: 404, description: 'File not found')]
    #[OA\Response(response: 422, description: 'Unable to read the archive')]
    public function archive(
        #[MapEntity(id: 'id', message: 'Файл не найден.')]
        File $file,
        Request $request,
        FileAccess $fileAccess,
        FileArchive $fileArchive,
    ): JsonResponse {
        $fileAccess->assertCanAccess($file, $request);

        return $this->json(new ArchiveListResponse(
            $fileArchive->entries($file, $request->headers->get('X-Archive-Password')),
        ));
    }

    #[Route('/{id}/archive/entry', name: 'archive_entry', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Archive entry contents',
        content: new OA\MediaType(
            mediaType: 'application/octet-stream',
            schema: new OA\Schema(type: 'string', format: 'binary'),
        ),
    )]
    #[OA\Response(response: 403, description: 'Archive password required or invalid')]
    #[OA\Response(response: 404, description: 'Archive entry not found')]
    #[OA\Response(response: 422, description: 'Unable to read the archive')]
    public function archiveEntry(
        #[MapEntity(id: 'id', message: 'Файл не найден.')]
        File $file,
        Request $request,
        FileAccess $fileAccess,
        FileArchive $fileArchive,
    ): BinaryFileResponse {
        $fileAccess->assertCanAccess($file, $request);

        $path = (string) $request->query->get('path', '');
        $temporaryFile = $fileArchive->extract($file, $path, $request->headers->get('X-Archive-Password'));

        $response = new BinaryFileResponse($temporaryFile);
        $response->deleteFileAfterSend(true);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            \basename(\str_replace('\\', '/', $path)),
        );
        $response->headers->set('Content-Type', 'application/octet-stream');

        return $response;
    }
}
