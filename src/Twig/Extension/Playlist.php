<?php

namespace App\Twig\Extension;

use App\Service\Playlist as PlaylistHelper;
use Exception;
use M3uParser\M3uData;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Playlist extends AbstractExtension
{
    /**
     * @var PlaylistHelper
     */
    private $playlistHelper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(PlaylistHelper $playlistHelper, LoggerInterface $logger)
    {
        $this->playlistHelper = $playlistHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
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
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
    }
}
