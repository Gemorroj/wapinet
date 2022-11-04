<?php

namespace App\Twig\Extension;

use App\Service\Torrent as TorrentHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Torrent extends AbstractExtension
{
    private TorrentHelper $torrentHelper;
    private LoggerInterface $logger;

    public function __construct(TorrentHelper $torrentHelper, LoggerInterface $logger)
    {
        $this->torrentHelper = $torrentHelper;
        $this->logger = $logger;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_torrent_list', [$this, 'getList']),
        ];
    }

    public function getList(File $file): ?array
    {
        try {
            $data = $this->torrentHelper->decodeFile($file);
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage(), [$e]);

            return null;
        }

        $list = [];
        if (isset($data['info']['files'])) {
            foreach ($data['info']['files'] as $entry) {
                $list[] = [
                    'path' => \implode('/', $entry['path']),
                    'size' => $entry['length'],
                ];
            }
        } elseif (isset($data['info']['name'], $data['info']['length'])) {
            $list[] = [
                'path' => $data['info']['name'],
                'size' => $data['info']['length'],
            ];
        }

        return $list;
    }
}
