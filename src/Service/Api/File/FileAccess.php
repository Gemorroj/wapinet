<?php

namespace App\Service\Api\File;

use App\Entity\File;
use App\Exception\PublicHttpException;
use App\Security\FileVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class FileAccess
{
    public function __construct(
        private Security $security,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function assertCanAccess(File $file, Request $request): void
    {
        $privileged = $this->security->isGranted(FileVoter::VIEW, $file);

        if ($file->isHidden() && !$privileged) {
            throw new PublicHttpException(404, 'Файл не найден.');
        }

        if (null === $file->getPassword() || $privileged) {
            return;
        }

        $password = $request->headers->get('X-File-Password') ?? (string) $request->query->get('password', '');

        if ('' === $password) {
            throw new PublicHttpException(403, 'Для доступа к файлу требуется пароль.');
        }

        $hasher = $this->passwordHasherFactory->getPasswordHasher($file);

        if (!$hasher->verify($file->getPassword(), $password)) {
            throw new PublicHttpException(403, 'Неверный пароль к файлу.');
        }
    }
}
