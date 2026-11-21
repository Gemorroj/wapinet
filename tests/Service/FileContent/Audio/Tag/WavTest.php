<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Audio\Tag;

use App\Service\FileContent\Audio\Tag\AudioTagData;
use App\Service\FileContent\Audio\Tag\Wav;
use App\Service\FileContent\Getid3;
use PHPUnit\Framework\TestCase;

final class WavTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_wav_'.\bin2hex(\random_bytes(4));
        \mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testWritesId3TagsAndKeepsAudio(): void
    {
        $path = $this->copyFixture('sample.wav');
        $getid3 = new Getid3();
        $before = $getid3->analyze($path);
        $audio = \substr((string) \file_get_contents($path), (int) $before['avdataoffset'], (int) $before['avdataend'] - (int) $before['avdataoffset']);

        new Wav($getid3)->writeTags($path, new AudioTagData([
            'TITLE' => ['Title'],
            'ARTIST' => ['Artist'],
            'ALBUMARTIST' => ['Album Artist'],
            'COMPOSER' => ['Composer'],
            'LYRICS' => ['Lyrics'],
        ]));

        $after = $getid3->analyze($path);
        \getid3_lib::CopyTagsToComments($after);

        self::assertSame('wav', $after['fileformat']);
        self::assertSame(['Title'], $after['comments']['title']);
        self::assertSame(['Artist'], $after['comments']['artist']);
        self::assertSame(['Album Artist'], $after['comments']['band']);
        self::assertSame(['Composer'], $after['comments']['composer']);
        self::assertSame(['Lyrics'], $after['comments']['unsynchronised_lyric']);
        self::assertSame($audio, \substr((string) \file_get_contents($path), (int) $after['avdataoffset'], (int) $after['avdataend'] - (int) $after['avdataoffset']));
    }

    public function testWritesMp3InWavAndRemovesTags(): void
    {
        $path = $this->copyFixture('sample-mp3.wav');
        $getid3 = new Getid3();
        $wav = new Wav($getid3);

        $wav->writeTags($path, new AudioTagData(['TITLE' => ['Title'], 'ARTIST' => ['Artist']]));

        $info = $getid3->analyze($path);
        \getid3_lib::CopyTagsToComments($info);
        self::assertSame('wav', $info['fileformat']);
        self::assertSame('mp3', $info['audio']['dataformat']);
        self::assertSame(['Title'], $info['comments']['title']);

        $wav->clearTags($path);

        $info = $getid3->analyze($path);
        self::assertSame('wav', $info['fileformat']);
        self::assertSame('mp3', $info['audio']['dataformat']);
        self::assertArrayNotHasKey('id3v2', $info);
    }

    public function testRemovesNonstandardId3Prefix(): void
    {
        $path = $this->copyFixture('sample.wav');
        $id3 = 'ID3'."\x04\x00\x00".\pack('V', 0);
        \file_put_contents($path, $id3.(string) \file_get_contents($path));

        new Wav(new Getid3())->writeTags($path, new AudioTagData(['TITLE' => ['Title']]));

        $result = (string) \file_get_contents($path);

        self::assertStringStartsWith('RIFF', $result);
    }

    public function testKeepsUnknownChunksAndTheirPadding(): void
    {
        $path = $this->write('odd.wav', $this->riff([
            'fmt ' => \pack('vvVVvv', 1, 1, 8000, 8000, 1, 8),
            'JUNK' => 'XYZ',
            'data' => 'abcd',
        ]));

        new Wav(new Getid3())->writeTags($path, new AudioTagData(['TITLE' => ['Title']]));

        $result = (string) \file_get_contents($path);
        $size = \unpack('Vsize', \substr($result, 4, 4));

        self::assertStringStartsWith('RIFF', $result);
        self::assertStringContainsString('JUNK', $result);
        self::assertStringContainsString("XYZ\x00", $result);
        self::assertStringContainsString('abcd', $result);
        self::assertIsArray($size);
        self::assertSame(\strlen($result) - 8, $size['size']);
    }

    public function testReplacesListInfoChunkWithId3(): void
    {
        $info = 'INFOIART'.\pack('V', 4).'abcd';
        $path = $this->write('list.wav', $this->riff([
            'fmt ' => \pack('vvVVvv', 1, 1, 8000, 8000, 1, 8),
            'LIST' => $info,
            'data' => 'wxyz',
        ]));

        new Wav(new Getid3())->writeTags($path, new AudioTagData(['TITLE' => ['Title']]));

        $result = (string) \file_get_contents($path);

        self::assertStringNotContainsString('IART', $result);
        self::assertStringContainsString('id3 ', $result);
        self::assertStringContainsString('wxyz', $result);
    }

    public function testRejectsNonRiffFile(): void
    {
        $path = $this->write('broken.wav', 'RIFFnot a wave file');

        $this->expectException(\RuntimeException::class);

        new Wav(new Getid3())->writeTags($path, new AudioTagData(['TITLE' => ['Title']]));
    }

    public function testRejectsTruncatedChunk(): void
    {
        $path = $this->write('truncated.wav', 'RIFF'.\pack('V', 100).'WAVEfmt '.\pack('V', 16).'short');

        $this->expectException(\RuntimeException::class);

        new Wav(new Getid3())->writeTags($path, new AudioTagData(['TITLE' => ['Title']]));
    }

    /**
     * @param array<string, string> $chunks
     */
    private function riff(array $chunks): string
    {
        $body = '';

        foreach ($chunks as $id => $data) {
            $body .= $id.\pack('V', \strlen($data)).$data.(0 !== \strlen($data) % 2 ? "\x00" : '');
        }

        return 'RIFF'.\pack('V', 4 + \strlen($body)).'WAVE'.$body;
    }

    private function write(string $name, string $contents): string
    {
        $path = $this->directory.'/'.$name;
        \file_put_contents($path, $contents);

        return $path;
    }

    private function copyFixture(string $name): string
    {
        $path = $this->directory.'/'.$name;
        \copy(__DIR__.'/../../../../fixtures/audio/'.$name, $path);

        return $path;
    }
}
