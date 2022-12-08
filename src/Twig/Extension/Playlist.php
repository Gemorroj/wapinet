<?php

namespace App\Twig\Extension;

use App\Service\Playlist as PlaylistHelper;
use M3uParser\M3uData;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Playlist extends AbstractExtension
{
    public function __construct(private PlaylistHelper $playlistHelper, private LoggerInterface $logger)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_playlist_list', [$this, 'getList']),
        ];
    }

    public function getList(File $file): ?M3uData
    {
        try {
            return $this->playlistHelper->parseFile($file);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
    }
}
