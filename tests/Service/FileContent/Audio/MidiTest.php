<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Audio;

use App\Service\FileContent\Audio\Midi;
use PHPUnit\Framework\TestCase;

final class MidiTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_midi_'.\bin2hex(\random_bytes(4));
        \mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testComputesDurationFromSingleTrack(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testUsesTempoFromAnotherTrackAndTakesTheLongest(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000).$this->end(),
            $this->noteOn(60).$this->noteOff(60, 192).$this->end(),
        ]));

        self::assertSame(1.0, $duration);
    }

    public function testAppliesTempoChanges(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->noteOn(60)
            .$this->tempo(250000, 96)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.75, $duration);
    }

    public function testLastTempoOnTheSameTickWins(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->tempo(1000000)
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(1.0, $duration);
    }

    public function testIgnoresZeroTempo(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(0)
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testSupportsRunningStatus(): void
    {
        $runningNote = $this->variableLength(48).\chr(62).\chr(100);
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->noteOn(60).$runningNote
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.75, $duration);
    }

    public function testRunningStatusIsResetByMetaEvents(): void
    {
        $runningNote = $this->variableLength(0).\chr(62).\chr(100);

        self::assertNull($this->duration($this->midi([
            $this->noteOn(60).$this->tempo(500000).$runningNote.$this->end(),
        ], 0)));
    }

    public function testSkipsSysExEvents(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->variableLength(0)."\xF0".$this->variableLength(4).'data'
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testSkipsSystemCommonEvents(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->variableLength(0)."\xF2".\pack('n', 0)
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testSupportsOneByteChannelMessages(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->variableLength(0)."\xC0".\chr(5)
            .$this->variableLength(0)."\xD0".\chr(64)
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testIgnoresUnknownMetaEvents(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000)
            .$this->variableLength(0)."\xFF\x01".$this->variableLength(5).'Title'
            .$this->noteOn(60)
            .$this->noteOff(60, 96)
            .$this->end(),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testParsesTrackWithoutEndOfTrack(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96),
        ], 0));

        self::assertSame(0.5, $duration);
    }

    public function testReturnsZeroForEmptyTrack(): void
    {
        self::assertSame(0.0, $this->duration($this->midi([$this->end()], 0)));
    }

    public function testSupportsSmpteDivision(): void
    {
        $division = ((256 - 25) << 8) | 40;
        $duration = $this->duration($this->midi([
            $this->noteOn(60).$this->noteOff(60, 100).$this->end(),
        ], 0, $division));

        self::assertEqualsWithDelta(0.1, $duration, 0.0001);
    }

    public function testSkipsExtraHeaderBytes(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96).$this->end(),
        ], 0, 96, "\x00\x00"));

        self::assertSame(0.5, $duration);
    }

    public function testParsesTruncatedChunkWithCompleteEvents(): void
    {
        $track = $this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96).$this->end();
        $data = $this->header(0, 1, 96).$this->chunk($track, \strlen($track) + 100);

        self::assertSame(0.5, $this->duration($data));
    }

    public function testIgnoresTrailingDataAfterTracks(): void
    {
        $data = $this->midi([$this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96).$this->end()], 0)
            .'trailing garbage';

        self::assertSame(0.5, $this->duration($data));
    }

    public function testFormatTwoUsesTheLongestSequence(): void
    {
        $duration = $this->duration($this->midi([
            $this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96).$this->end(),
            $this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 192).$this->end(),
        ], 2));

        self::assertSame(1.0, $duration);
    }

    public function testReturnsNullForTruncatedTrack(): void
    {
        $data = $this->header(0, 1, 96).'MTrk'.\pack('N', 100)."\x00\x90";

        self::assertNull($this->duration($data));
    }

    public function testReturnsNullForZeroDivision(): void
    {
        self::assertNull($this->duration($this->midi([$this->end()], 0, 0)));
    }

    public function testReturnsNullWithoutHeader(): void
    {
        self::assertNull($this->duration('not a midi file'));
    }

    public function testReturnsNullForEmptyFile(): void
    {
        self::assertNull($this->duration(''));
    }

    public function testReturnsNullForMissingFile(): void
    {
        self::assertNull(new Midi()->getDuration($this->directory.'/missing.mid'));
    }

    public function testUnwrapsRiffContainer(): void
    {
        $midi = $this->midi([$this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96).$this->end()], 0);
        $riff = 'RIFF'.\pack('V', 4 + 8 + \strlen($midi)).'RMID'
            .'data'.\pack('V', \strlen($midi)).$midi;

        self::assertSame(0.5, $this->duration($riff));
    }

    public function testSkipsId3Prefix(): void
    {
        $midi = $this->midi([$this->tempo(500000).$this->noteOn(60).$this->noteOff(60, 96).$this->end()], 0);
        $id3 = 'ID3'."\x04\x00\x00\x00\x00\x00\x0A".\str_repeat("\x00", 10);

        self::assertSame(0.5, $this->duration($id3.$midi));
    }

    /**
     * @param list<string> $tracks
     */
    private function midi(array $tracks, int $format = 1, int $division = 96, string $headerExtra = ''): string
    {
        $data = $this->header($format, \count($tracks), $division, $headerExtra);

        foreach ($tracks as $track) {
            $data .= $this->chunk($track);
        }

        return $data;
    }

    private function header(int $format, int $tracks, int $division, string $extra = ''): string
    {
        return 'MThd'
            .\pack('N', 6 + \strlen($extra))
            .\pack('n', $format)
            .\pack('n', $tracks)
            .\pack('n', $division & 0xFFFF)
            .$extra;
    }

    private function chunk(string $data, ?int $length = null): string
    {
        return 'MTrk'.\pack('N', $length ?? \strlen($data)).$data;
    }

    private function duration(string $contents): ?float
    {
        $path = $this->directory.'/file.mid';
        \file_put_contents($path, $contents);

        return new Midi()->getDuration($path);
    }

    private function tempo(int $microseconds, int $delta = 0): string
    {
        return $this->variableLength($delta)
            ."\xFF\x51\x03"
            .\chr(($microseconds >> 16) & 0xFF)
            .\chr(($microseconds >> 8) & 0xFF)
            .\chr($microseconds & 0xFF);
    }

    private function noteOn(int $note, int $delta = 0): string
    {
        return $this->variableLength($delta)."\x90".\chr($note).\chr(100);
    }

    private function noteOff(int $note, int $delta = 0): string
    {
        return $this->variableLength($delta)."\x80".\chr($note).\chr(0);
    }

    private function end(int $delta = 0): string
    {
        return $this->variableLength($delta)."\xFF\x2F\x00";
    }

    private function variableLength(int $value): string
    {
        $bytes = [$value & 0x7F];

        while ($value >>= 7) {
            $bytes[] = ($value & 0x7F) | 0x80;
        }

        return \implode('', \array_map(\chr(...), \array_reverse($bytes)));
    }
}
