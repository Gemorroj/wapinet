<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Hash;

use App\Service\Api\Hash\FileHasher;
use PHPUnit\Framework\TestCase;

final class FileHasherTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'hash_test');

        if (false === $path) {
            self::fail('Не удалось создать временный файл.');
        }

        $this->path = $path;
    }

    protected function tearDown(): void
    {
        @\unlink($this->path);
    }

    public function testHashesAllAlgorithms(): void
    {
        \file_put_contents($this->path, 'abc');

        $hashes = new FileHasher()->hash($this->path, FileHasher::algorithmIds());

        self::assertSame([
            'md5' => '900150983cd24fb0d6963f7d28e17f72',
            'sha1' => 'a9993e364706816aba3e25717850c26c9cd0d89d',
            'sha224' => '23097d223405d8228642a477bda255b32aadbce4bda0b3f7e36c9da7',
            'sha256' => 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
            'sha384' => 'cb00753f45a35e8bb5a03d699ac65007272c32ab0eded1631a8b605a43ff5bed8086072ba1e7cc2358baeca134c825a7',
            'sha512' => 'ddaf35a193617abacc417349ae20413112e6fa4e89a97ea20a9eeee64b55d39a2192992a274fc1a836ba3c23a3feebbd454d4423643ce80e2a9ac94fa54ca49f',
            'sha512-224' => '4634270f707b6a54daae7530460842e20e37ed265ceee9a43e8924aa',
            'sha512-256' => '53048e2681941ef99b2e29b76b4c7dabe4c2d0c634fc6d46e0e2f13107e7af23',
            'sha3-224' => 'e642824c3f8cf24ad09234ee7d3c766fc9a3a5168d0c94ad73b46fdf',
            'sha3-256' => '3a985da74fe225b2045c172d6bd390bd855f086e3e9d525b46bfe24511431532',
            'sha3-384' => 'ec01498288516fc926459f58e2c6ad8df9b473cb0fc08c2596da7cf0e49be4b298d88cea927ac7f539f1edf228376d25',
            'sha3-512' => 'b751850b1a57168a5693cd924b6b096e08f621827444f70d884f5d0240d2712e10e116e9192af3c91a7ec57647e3934057340b4cf408d5a56592f8274eec53f0',
            'ripemd160' => '8eb208f7e05d987a9b044a8e98c6b087f15a0bfc',
            'crc32' => '352441c2',
            'crc32c' => '364b3fb7',
        ], $hashes);
    }

    public function testHashesOnlyRequestedAlgorithms(): void
    {
        \file_put_contents($this->path, 'abc');

        self::assertSame([
            'sha256' => 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
            'crc32' => '352441c2',
        ], new FileHasher()->hash($this->path, ['sha256', 'crc32']));
    }

    public function testHashesFileLargerThanOneChunk(): void
    {
        $content = \str_repeat('a', 100_000);

        \file_put_contents($this->path, $content);

        $hashes = new FileHasher()->hash($this->path, ['md5', 'sha256', 'sha3-256', 'crc32']);

        self::assertSame(\hash('md5', $content), $hashes['md5']);
        self::assertSame(\hash('sha256', $content), $hashes['sha256']);
        self::assertSame(\hash('sha3-256', $content), $hashes['sha3-256']);
        self::assertSame(\hash('crc32b', $content), $hashes['crc32']);
    }

    public function testRejectsUnknownAlgorithm(): void
    {
        \file_put_contents($this->path, 'abc');

        $this->expectException(\InvalidArgumentException::class);

        new FileHasher()->hash($this->path, ['md5', 'unknown']);
    }

    public function testFailsForUnreadableFile(): void
    {
        $this->expectException(\RuntimeException::class);

        new FileHasher()->hash($this->path.'-missing', ['md5']);
    }
}


