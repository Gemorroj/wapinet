<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Tags;

use App\Service\FileContent\Backend\Getid3;
use App\Service\FileContent\Tags\AudioTagData;
use App\Service\FileContent\Tags\Wav;
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

    private function copyFixture(string $name): string
    {
        $path = $this->directory.'/'.$name;
        \copy(__DIR__.'/../../../fixtures/audio/'.$name, $path);

        return $path;
    }
}
