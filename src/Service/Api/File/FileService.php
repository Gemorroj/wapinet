<?php

namespace App\Service\Api\File;

use App\Dto\Api\File\FileCreateRequest;
use App\Dto\Api\File\FileUpdateRequest;
use App\Entity\File;
use App\Entity\FileTags;
use App\Entity\User;
use App\Message\FileAddMessage;
use App\Message\FileConvertMessage;
use App\Message\FilePrepareMessage;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Service\FileManager as FileHelper;
use App\Service\MimeGuesser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class FileService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileRepository $fileRepository,
        private TagRepository $tagRepository,
        private MimeGuesser $mimeGuesser,
        private FileHelper $fileHelper,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private MessageBusInterface $messageBus,
        private AuthorizationCheckerInterface $authorizationChecker,
        private RemoteFileFetcher $remoteFileFetcher,
    ) {
    }

    public function create(FileCreateRequest $payload, Request $request, ?User $user): File
    {
        $uploadedFile = $this->uploadedFile($payload->file, $payload->url);
        if (null === $uploadedFile) {
            throw new UnprocessableEntityHttpException('Укажите файл или ссылку.');
        }

        $hash = $this->hash($uploadedFile);
        $this->assertUniqueHash($hash);

        $file = new File();
        $file->setHash($hash);
        $file->setOriginalFileName($this->originalFileName($uploadedFile));
        $file->setMimeType($this->mimeGuesser->getMimeType($uploadedFile));
        $file->setDescription($payload->description);
        $file->setUser($user);
        $file->setIp($request->getClientIp() ?? '');
        $file->setBrowser((string) $request->headers->get('User-Agent', ''));
        $file->setFile($uploadedFile);

        if (null !== $payload->password && '' !== $payload->password) {
            $file->setPassword($this->passwordHasherFactory->getPasswordHasher($file)->hash($payload->password));

            // Anonymous uploads always wait for moderation, even when password-protected.
            if (null !== $user) {
                $file->setHidden(false);
            }
        } else {
            $this->syncTags($file, $payload->tags);
        }

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new FileAddMessage((int) $file->getId()));
        $this->dispatchPreparation($file);

        return $file;
    }

    public function update(File $file, FileUpdateRequest $payload): void
    {
        if (null !== $payload->file && null !== $payload->url && '' !== $payload->url) {
            throw new UnprocessableEntityHttpException('Укажите только файл или только ссылку.');
        }

        $oldFile = clone $file;
        $uploadedFile = $this->uploadedFile($payload->file, $payload->url);

        if (null !== $uploadedFile) {
            $hash = $this->hash($uploadedFile);
            $this->assertUniqueHash($hash, $file);

            $file->setHash($hash);
            $file->setOriginalFileName($this->originalFileName($uploadedFile));
            $file->setMimeType($this->mimeGuesser->getMimeType($uploadedFile));
            $file->setFile($uploadedFile);
            // The new content needs fresh dimensions, codecs and previews.
            $file->setMeta(null);
        }

        if (null !== $payload->description) {
            $file->setDescription($payload->description);
        }

        if (null !== $payload->hidden) {
            if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('Только администраторы могут менять видимость файла.');
            }

            $file->setHidden($payload->hidden);
        }

        if (null !== $payload->password && '' !== $payload->password) {
            $this->fileHelper->setPassword($file, $payload->password);
            $file->setHidden(false);
            $this->syncTags($file, []);
        } else {
            if ('' === $payload->password) {
                $this->fileHelper->removePassword($file);
            }

            if (null !== $payload->tags) {
                $this->syncTags($file, $payload->tags);
            }
        }

        $file->setUpdatedAtValue();

        $this->entityManager->persist($file);

        if (null !== $uploadedFile) {
            $this->fileHelper->cleanupFile($oldFile);
        }

        $this->entityManager->flush();

        if (null !== $uploadedFile) {
            $this->dispatchPreparation($file);
        }
    }

    public function delete(File $file): void
    {
        $this->fileHelper->cleanupFile($file);
        $this->entityManager->remove($file);
        $this->entityManager->flush();
    }

    private function uploadedFile(?UploadedFile $file, ?string $url): ?UploadedFile
    {
        if (null !== $file) {
            return $file;
        }

        if (null !== $url && '' !== $url) {
            return $this->remoteFileFetcher->fetch($url);
        }

        return null;
    }

    /**
     * Metadata and previews are built by the worker: the request must not wait for ffmpeg/getID3.
     */
    private function dispatchPreparation(File $file): void
    {
        $fileId = (int) $file->getId();
        $conversion = $file->getConversion();

        $this->messageBus->dispatch(new FilePrepareMessage($fileId));

        if (null !== $conversion) {
            $this->messageBus->dispatch(new FileConvertMessage($fileId, $conversion));
        }
    }

    /**
     * @param string[] $names
     */
    private function syncTags(File $file, array $names): void
    {
        $names = \array_values(\array_unique(\array_filter(
            \array_map(static fn (string $name): string => \trim($name), $names),
            static fn (string $name): bool => '' !== $name,
        )));

        $tags = $this->tagRepository->makeTags($names);

        foreach ($file->getFileTags()->toArray() as $fileTag) {
            if (!$tags->contains($fileTag->getTag())) {
                $file->getFileTags()->removeElement($fileTag);
                $this->entityManager->remove($fileTag);
            }
        }

        foreach ($tags as $tag) {
            $exists = false;
            foreach ($file->getFileTags() as $fileTag) {
                if ($fileTag->getTag() === $tag || $fileTag->getTag()?->getName() === $tag->getName()) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $file->getFileTags()->add(new FileTags()->setFile($file)->setTag($tag));
            }
        }

        $file->setTags($tags);
    }

    private function assertUniqueHash(string $hash, ?File $current = null): void
    {
        $existing = $this->fileRepository->findOneBy(['hash' => $hash]);

        if (null !== $existing && $existing !== $current) {
            throw new ConflictHttpException('Файл с таким содержимым уже существует.');
        }
    }

    private function hash(UploadedFile $file): string
    {
        $hash = \md5_file($file->getPathname());

        if (false === $hash) {
            throw new \RuntimeException('Unable to read the uploaded file.');
        }

        return $hash;
    }

    private function originalFileName(UploadedFile $file): string
    {
        $basename = \basename($file->getClientOriginalName());

        return '..' === $basename ? '' : $basename;
    }
}
