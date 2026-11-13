<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Tags;

use App\Service\FileContent\Tags\AudioTagData;
use App\Service\FileContent\Tags\Ogg;
use PHPUnit\Framework\TestCase;

final class OggTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_ogg_'.\bin2hex(\random_bytes(4));
        \mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testEmptiesCommentPacket(): void
    {
        $comment = $this->commentPacket(['TITLE' => 'Title', 'ARTIST' => 'Artist']);
        $audioPage = $this->page(1, 2, 0, ["\x01audio"], 1000);
        $path = $this->write('sample.ogg', $this->page(1, 0, 0x02, ["\x01vorbis".\str_repeat('I', 20)])
            .$this->page(1, 1, 0, [$comment, "\x05vorbis".\str_repeat('S', 30)])
            .$audioPage);

        new Ogg()->clearTags($path);

        $result = (string) \file_get_contents($path);
        $packets = $this->packets($result);

        self::assertCount(4, $packets);
        self::assertSame("\x01audio", $packets[3]);
        self::assertStringStartsWith("\x03vorbis", $packets[1]);
        self::assertSame(0, $this->commentCount($packets[1]));
        self::assertSame(1, \ord($packets[1][\strlen($packets[1]) - 1]));
    }

    public function testEmptiesCommentPacketSplitBetweenPages(): void
    {
        $comment = $this->commentPacket(['TITLE' => \str_repeat('T', 300)]);
        $first = \substr($comment, 0, 255);
        $second = \substr($comment, 255);
        $audioPage = $this->page(7, 3, 0, ["\x01audio"], 1000);
        $path = $this->write('sample.ogg', $this->page(7, 0, 0x02, ["\x01vorbis".\str_repeat('I', 20)])
            .$this->rawPage(7, 1, 0, [255], $first)
            .$this->rawPage(7, 2, 0x01, [\strlen($second), 4], $second."\x05vor")
            .$audioPage);

        new Ogg()->clearTags($path);

        $result = (string) \file_get_contents($path);
        $packets = $this->packets($result);

        self::assertCount(4, $packets);
        self::assertSame("\x01audio", $packets[3]);
        self::assertSame(0, $this->commentCount($packets[1]));
    }

    public function testWritesLongCommentAndKeepsAudio(): void
    {
        $old = $this->commentPacket(['TITLE' => 'Old']);
        $audioPage = $this->page(1, 2, 0, ["\x01audio"], 1000);
        $path = $this->write('sample.ogg', $this->page(1, 0, 0x02, ["\x01vorbis".\str_repeat('I', 20)])
            .$this->page(1, 1, 0, [$old, "\x05vorbis".\str_repeat('S', 30)])
            .$audioPage);
        $lyrics = \str_repeat('L', 700);

        new Ogg()->writeTags($path, new AudioTagData(['TITLE' => ['New'], 'LYRICS' => [$lyrics]]));

        $packets = $this->packets((string) \file_get_contents($path));

        self::assertCount(4, $packets);
        self::assertSame("\x01audio", $packets[3]);
        self::assertStringContainsString('TITLE=New', $packets[1]);
        self::assertStringContainsString('LYRICS='.$lyrics, $packets[1]);
    }

    public function testWritesAndRemovesPicture(): void
    {
        $old = $this->commentPacket(['TITLE' => 'Old']);
        $path = $this->write('sample.ogg', $this->page(1, 0, 0x02, ["\x01vorbis".\str_repeat('I', 20)])
            .$this->page(1, 1, 0, [$old, "\x05vorbis".\str_repeat('S', 30)])
            .$this->page(1, 2, 0, ["\x01audio"], 1000));
        $cover = (string) \file_get_contents(__DIR__.'/../../../fixtures/audio/cover.jpg');

        new Ogg()->writeTags($path, new AudioTagData(['TITLE' => ['With cover']], false, $cover, 'image/jpeg'));

        $packets = $this->packets((string) \file_get_contents($path));
        self::assertStringContainsString('METADATA_BLOCK_PICTURE=', $packets[1]);

        new Ogg()->writeTags($path, new AudioTagData(['TITLE' => ['Without cover']], true));

        $packets = $this->packets((string) \file_get_contents($path));
        self::assertStringNotContainsString('METADATA_BLOCK_PICTURE=', $packets[1]);
    }

    public function testLeavesFileWithoutVorbisCommentUntouched(): void
    {
        $contents = $this->page(1, 0, 0x02, ["\x80theora".\str_repeat('V', 20)])
            .$this->page(1, 1, 0, ["\x01audio"], 1000);
        $path = $this->write('sample.ogg', $contents);

        new Ogg()->clearTags($path);

        self::assertSame($contents, \file_get_contents($path));
    }

    private function write(string $name, string $contents): string
    {
        $path = $this->directory.'/'.$name;
        \file_put_contents($path, $contents);

        return $path;
    }

    /**
     * @param array<string, string> $values
     */
    private function commentPacket(array $values): string
    {
        $packet = "\x03vorbis".\pack('V', 6).'vendor'.\pack('V', \count($values));

        foreach ($values as $name => $value) {
            $comment = $name.'='.$value;
            $packet .= \pack('V', \strlen($comment)).$comment;
        }

        return $packet."\x01";
    }

    private function page(int $serial, int $sequence, int $headerType, array $packets, int $granule = 0): string
    {
        $segments = [];
        $payload = '';

        foreach ($packets as $packet) {
            $remaining = \strlen($packet);

            while ($remaining >= 255) {
                $segments[] = 255;
                $remaining -= 255;
            }

            $segments[] = $remaining;
            $payload .= $packet;
        }

        return $this->rawPage($serial, $sequence, $headerType, $segments, $payload, $granule);
    }

    /**
     * @param list<int> $segments
     */
    private function rawPage(int $serial, int $sequence, int $headerType, array $segments, string $payload, int $granule = 0): string
    {
        return 'OggS'."\x00".\chr($headerType).\pack('P', $granule).\pack('V', $serial).\pack('V', $sequence)
            ."\x00\x00\x00\x00".\chr(\count($segments)).\implode('', \array_map(static fn (int $length): string => \chr($length), $segments)).$payload;
    }

    /**
     * @return list<string>
     */
    private function packets(string $data): array
    {
        $packets = [];
        $buffer = '';
        $offset = 0;
        $limit = \strlen($data);

        while ($offset + 27 <= $limit && 'OggS' === \substr($data, $offset, 4)) {
            $segmentCount = \ord($data[$offset + 26]);
            $segments = \substr($data, $offset + 27, $segmentCount);
            $position = $offset + 27 + $segmentCount;

            for ($index = 0; $index < $segmentCount; ++$index) {
                $length = \ord($segments[$index]);
                $buffer .= \substr($data, $position, $length);
                $position += $length;

                if ($length < 255) {
                    $packets[] = $buffer;
                    $buffer = '';
                }
            }

            $offset = $position;
        }

        return $packets;
    }

    private function commentCount(string $packet): int
    {
        $vendorLength = \unpack('Vlength', \substr($packet, 7, 4));

        self::assertIsArray($vendorLength);

        return (int) \unpack('Vcount', \substr($packet, 11 + (int) $vendorLength['length'], 4))['count'];
    }
}
