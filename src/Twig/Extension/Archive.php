<?php

namespace App\Twig\Extension;

use App\Service\Archiver\Archive7z;
use Archive7z\Entry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Archive extends AbstractExtension
{
    public function __construct(private readonly Archive7z $archive7z, private readonly LoggerInterface $logger)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_archive_list', $this->getList(...)),
        ];
    }

    /**
     * @return Entry[]|null
     */
    public function getList(File $file, ?int $limit = null): ?array
    {
        try {
            $entries = $this->archive7z->getEntries($file, $limit);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
            $entries = null;
        }

        return $entries;
    }
}
