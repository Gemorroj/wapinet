<?php

namespace App\Service\FileContent;

final readonly class Getid3
{
    public function getId3(): \getID3
    {
        $getid3 = new \getID3();
        $getid3->encoding = 'UTF-8';

        return $getid3;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $path): array
    {
        $info = $this->getId3()->analyze($path);
        \getid3_lib::CopyTagsToComments($info);

        return $info;
    }

    /**
     * Embedded cover art of an audio file.
     *
     * @return array{data: string, mime: string}|null
     */
    public function getCover(string $path): ?array
    {
        $picture = $this->analyze($path)['comments']['picture'][0] ?? null;

        if (!\is_array($picture) || !isset($picture['data'], $picture['image_mime'])) {
            return null;
        }

        return ['data' => $picture['data'], 'mime' => $picture['image_mime']];
    }

    public function getId3Writer(): \getid3_writetags
    {
        if (!\defined('GETID3_INCLUDEPATH')) {
            $this->getId3();
        }
        include_once GETID3_INCLUDEPATH.'write.php';

        $writer = new \getid3_writetags();
        $writer->tag_encoding = 'UTF-8';

        return $writer;
    }
}
