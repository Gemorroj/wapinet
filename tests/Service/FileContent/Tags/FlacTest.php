<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Tags;

use App\Service\FileContent\Tags\AudioTagData;
use App\Service\FileContent\Tags\Flac;
use PHPUnit\Framework\TestCase;

final class FlacTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_flac_'.\bin2hex(\random_bytes(4));
        \mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testRemovesCommentAndPictureBlocks(): void
    {
        $audio = "\xFF\xF8".\str_repeat("\xAA", 64);
        $path = $this->write('sample.flac', 'fLaC'
            .$this->block(0, \str_repeat("\x00", 34))
            .$this->block(4, "vendor\x00comment")
            .$this->block(6, 'picture')
            .$this->block(1, \str_repeat("\x00", 8), true)
            .$audio);

        new Flac()->clearTags($path);

        $result = (string) \file_get_contents($path);
        $blocks = $this->metadataBlocks($result);

        self::assertStringStartsWith('fLaC', $result);
        self::assertStringEndsWith($audio, $result);
        self::assertSame([0, 1], \array_column($blocks, 'type'));
        self::assertSame([false, true], \array_column($blocks, 'last'));
        self::assertStringNotContainsString('vendor', $result);
        self::assertStringNotContainsString('picture', $result);
    }

    public function testWritesCommentsAndKeepsOtherBlocks(): void
    {
        $audio = "\xFF\xF8".\str_repeat("\xAA", 64);
        $path = $this->write('sample.flac', 'fLaC'
            .$this->block(0, \str_repeat("\x00", 34))
            .$this->commentBlock(['TITLE' => 'Old'])
            .$this->block(1, \str_repeat("\x00", 8), true)
            .$audio);

        new Flac()->writeTags($path, new AudioTagData(['TITLE' => ['New'], 'ARTIST' => ['Artist']]));

        $result = (string) \file_get_contents($path);
        $blocks = $this->metadataBlocks($result);

        self::assertSame([0, 4, 1], \array_column($blocks, 'type'));
        self::assertSame([false, false, true], \array_column($blocks, 'last'));
        self::assertStringContainsString('TITLE=New', $blocks[1]['data']);
        self::assertStringContainsString('ARTIST=Artist', $blocks[1]['data']);
        self::assertStringNotContainsString('TITLE=Old', $blocks[1]['data']);
        self::assertStringEndsWith($audio, $result);
    }

    public function testWritesAndRemovesPicture(): void
    {
        $audio = "\xFF\xF8".\str_repeat("\xAA", 32);
        $path = $this->write('sample.flac', 'fLaC'
            .$this->block(0, \str_repeat("\x00", 34))
            .$this->commentBlock(['TITLE' => 'Old'])
            .$this->block(1, \str_repeat("\x00", 8), true)
            .$audio);
        $cover = (string) \file_get_contents(__DIR__.'/../../../fixtures/audio/cover.jpg');

        new Flac()->writeTags($path, new AudioTagData(['TITLE' => ['With cover']], false, $cover, 'image/jpeg'));

        $blocks = $this->metadataBlocks((string) \file_get_contents($path));

        self::assertSame([0, 4, 6, 1], \array_column($blocks, 'type'));
        self::assertStringContainsString($cover, $blocks[2]['data']);

        new Flac()->writeTags($path, new AudioTagData(['TITLE' => ['Without cover']], true));

        $blocks = $this->metadataBlocks((string) \file_get_contents($path));

        self::assertSame([0, 4, 1], \array_column($blocks, 'type'));
        self::assertStringEndsWith($audio, (string) \file_get_contents($path));
    }

    public function testMarksStreamInfoAsLastWhenNoOtherBlocksRemain(): void
    {
        $audio = "\xFF\xF8".\str_repeat("\xBB", 32);
        $path = $this->write('sample.flac', 'fLaC'
            .$this->block(0, \str_repeat("\x00", 34))
            .$this->block(4, 'comments', true)
            .$audio);

        new Flac()->clearTags($path);

        $result = (string) \file_get_contents($path);
        $blocks = $this->metadataBlocks($result);

        self::assertSame([0], \array_column($blocks, 'type'));
        self::assertSame([true], \array_column($blocks, 'last'));
        self::assertStringEndsWith($audio, $result);
    }

    public function testRemovesId3Prefix(): void
    {
        $tag = 'TAGDATA';
        $id3 = 'ID3'."\x04\x00\x00".\chr(0).\chr(0).\chr(0).\chr(\strlen($tag)).$tag;
        $path = $this->write('sample.flac', $id3.'fLaC'
            .$this->block(0, \str_repeat("\x00", 34))
            .$this->block(4, 'comments', true)
            .'audio');

        new Flac()->clearTags($path);

        $result = (string) \file_get_contents($path);

        self::assertStringStartsWith('fLaC', $result);
        self::assertStringNotContainsString('ID3', $result);
        self::assertStringEndsWith('audio', $result);
    }

    public function testRejectsForeignFileAndRemovesTemporaryFile(): void
    {
        $path = $this->write('sample.flac', 'not a flac file');

        try {
            new Flac()->clearTags($path);

            self::fail('Expected exception.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('FLAC', $exception->getMessage());
        }

        self::assertFileExists($path);
        self::assertSame('not a flac file', \file_get_contents($path));
        self::assertSame([], \glob($this->directory.'/flac*') ?: []);
    }

    private function write(string $name, string $contents): string
    {
        $path = $this->directory.'/'.$name;
        \file_put_contents($path, $contents);

        return $path;
    }

    private function block(int $type, string $data, bool $last = false): string
    {
        $length = \strlen($data);

        return \chr(($last ? 0x80 : 0) | $type)
            .\chr(($length >> 16) & 0xFF)
            .\chr(($length >> 8) & 0xFF)
            .\chr($length & 0xFF)
            .$data;
    }

    /**
     * @return list<array{type: int, last: bool, data: string}>
     */
    private function metadataBlocks(string $file): array
    {
        $blocks = [];
        $offset = 4;

        while (isset($file[$offset])) {
            $header = \ord($file[$offset]);
            $length = (\ord($file[$offset + 1]) << 16) | (\ord($file[$offset + 2]) << 8) | \ord($file[$offset + 3]);
            $blocks[] = ['type' => $header & 0x7F, 'last' => 0 !== ($header & 0x80), 'data' => \substr($file, $offset + 4, $length)];
            $offset += 4 + $length;

            if (0 !== ($header & 0x80)) {
                break;
            }
        }

        return $blocks;
    }

    /**
     * @param array<string, string> $values
     */
    private function commentBlock(array $values): string
    {
        $data = \pack('V', 6).'vendor'.\pack('V', \count($values));

        foreach ($values as $name => $value) {
            $comment = $name.'='.$value;
            $data .= \pack('V', \strlen($comment)).$comment;
        }

        return $this->block(4, $data);
    }
}
