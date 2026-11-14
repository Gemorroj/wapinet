<?php

declare(strict_types=1);

namespace App\Service\FileContent\Audio;

/**
 * Reads the play time of a Standard MIDI File (`.mid`, `.midi`, `.kar`, `.rmi`).
 *
 * Only the timing information is parsed: the header, track chunks, variable
 * length deltas and the tempo map. No notes, tracks or measures are built, so
 * it stays fast even for large files.
 */
final readonly class Midi
{
    private const int DEFAULT_TEMPO = 500000; // microseconds per quarter note

    /**
     * @return float|null duration in seconds, null when the file cannot be parsed
     */
    public function getDuration(string $filename): ?float
    {
        $data = @\file_get_contents($filename);

        if (false === $data) {
            return null;
        }

        $data = $this->unwrap($data);

        if (null === $data) {
            return null;
        }

        $headerOffset = \strpos($data, 'MThd');

        if (false === $headerOffset) {
            return null;
        }

        $headerLength = $this->uint32($data, $headerOffset + 4);

        if (null === $headerLength || $headerOffset + 8 + $headerLength > \strlen($data)) {
            return null;
        }

        $division = $this->int16($data, $headerOffset + 12);

        if (null === $division || 0 === $division) {
            return null;
        }

        $offset = $headerOffset + 8 + $headerLength;
        $limit = \strlen($data);
        $trackEnds = [];
        $tempos = [];

        while ($offset + 8 <= $limit && 'MTrk' === \substr($data, $offset, 4)) {
            $length = $this->uint32($data, $offset + 4);

            if (null === $length) {
                return null;
            }

            $start = $offset + 8;
            // A track may be truncated: parse what is there and stop at the data end.
            $end = \min($start + $length, $limit);
            $trackEnd = $this->track($data, $start, $end, $tempos);

            if (null === $trackEnd) {
                return null;
            }

            $trackEnds[] = $trackEnd;
            $offset = $start + $length;
        }

        if ([] === $trackEnds) {
            return null;
        }

        return $this->seconds(\max($trackEnds), $division, $tempos);
    }

    /**
     * @param list<array{int, int}> $tempos
     */
    private function track(string $data, int $start, int $end, array &$tempos): ?int
    {
        $offset = $start;
        $tick = 0;
        $runningStatus = null;

        while ($offset < $end) {
            $delta = $this->variableLength($data, $offset, $end);

            if (null === $delta) {
                return null;
            }

            $tick += $delta;

            if ($offset >= $end) {
                break;
            }

            $status = \ord($data[$offset]);

            if ($status < 0x80) {
                // Running status: the status byte is omitted.
                if (null === $runningStatus) {
                    return null;
                }

                $status = $runningStatus;
            } else {
                ++$offset;

                if ($status < 0xF0) {
                    $runningStatus = $status;
                } else {
                    $runningStatus = null;
                }
            }

            if (0xFF === $status) {
                if ($offset >= $end) {
                    return null;
                }

                $type = \ord($data[$offset]);
                ++$offset;
                $length = $this->variableLength($data, $offset, $end);

                if (null === $length || $offset + $length > $end) {
                    return null;
                }

                if (0x51 === $type && 3 === $length) {
                    $tempo = (\ord($data[$offset]) << 16) | (\ord($data[$offset + 1]) << 8) | \ord($data[$offset + 2]);

                    if ($tempo > 0) {
                        $tempos[] = [$tick, $tempo];
                    }
                }

                $offset += $length;

                if (0x2F === $type) {
                    break;
                }

                continue;
            }

            if (0xF0 === $status || 0xF7 === $status) {
                $length = $this->variableLength($data, $offset, $end);

                if (null === $length || $offset + $length > $end) {
                    return null;
                }

                $offset += $length;

                continue;
            }

            if ($status >= 0xF0) {
                // System common events that are not valid in files: skip their data.
                $size = match ($status) {
                    0xF1, 0xF3 => 1,
                    0xF2 => 2,
                    default => 0,
                };

                if ($offset + $size > $end) {
                    return null;
                }

                $offset += $size;

                continue;
            }

            $size = \in_array($status & 0xF0, [0xC0, 0xD0], true) ? 1 : 2;

            if ($offset + $size > $end) {
                return null;
            }

            $offset += $size;
        }

        return $tick;
    }

    /**
     * @param list<array{int, int}> $tempos
     */
    private function seconds(int $tick, int $division, array $tempos): float
    {
        if ($division < 0) {
            $high = ($division >> 8) & 0xFF;
            $fps = -($high >= 128 ? $high - 256 : $high);
            $ticksPerFrame = $division & 0xFF;

            if ($fps <= 0 || $ticksPerFrame <= 0) {
                return 0.0;
            }

            return $tick / ($fps * $ticksPerFrame);
        }

        \usort($tempos, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $seconds = 0.0;
        $previousTick = 0;
        $tempo = self::DEFAULT_TEMPO;

        foreach ($tempos as [$tempoTick, $tempoValue]) {
            if ($tempoTick > $tick) {
                break;
            }

            $seconds += ($tempoTick - $previousTick) * $tempo / 1_000_000 / $division;
            $previousTick = $tempoTick;
            $tempo = $tempoValue;
        }

        $seconds += ($tick - $previousTick) * $tempo / 1_000_000 / $division;

        return \max(0.0, $seconds);
    }

    private function variableLength(string $data, int &$offset, int $end): ?int
    {
        $value = 0;

        for ($i = 0; $i < 4; ++$i) {
            if ($offset >= $end) {
                return null;
            }

            $byte = \ord($data[$offset]);
            ++$offset;
            $value = ($value << 7) | ($byte & 0x7F);

            if (0 === ($byte & 0x80)) {
                return $value;
            }
        }

        return null;
    }

    private function uint32(string $data, int $offset): ?int
    {
        if ($offset + 4 > \strlen($data)) {
            return null;
        }

        $value = \unpack('Nvalue', \substr($data, $offset, 4));

        return \is_array($value) ? (int) $value['value'] : null;
    }

    private function uint32le(string $data, int $offset): ?int
    {
        if ($offset + 4 > \strlen($data)) {
            return null;
        }

        $value = \unpack('Vvalue', \substr($data, $offset, 4));

        return \is_array($value) ? (int) $value['value'] : null;
    }

    private function int16(string $data, int $offset): ?int
    {
        if ($offset + 2 > \strlen($data)) {
            return null;
        }

        $value = \unpack('nvalue', \substr($data, $offset, 2));

        if (!\is_array($value)) {
            return null;
        }

        $value = (int) $value['value'];

        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    /**
     * Removes an ID3v2 tag and a RIFF (RMID) container around the MIDI data.
     */
    private function unwrap(string $data): ?string
    {
        while (\str_starts_with($data, 'ID3') && \strlen($data) >= 10) {
            $size = ((\ord($data[6]) & 0x7F) << 21)
                | ((\ord($data[7]) & 0x7F) << 14)
                | ((\ord($data[8]) & 0x7F) << 7)
                | (\ord($data[9]) & 0x7F);
            $footer = 0 !== (\ord($data[5]) & 0x10) ? 10 : 0;
            $length = 10 + $size + $footer;

            if ($length >= \strlen($data)) {
                return null;
            }

            $data = \substr($data, $length);
        }

        if (\str_starts_with($data, 'RIFF') && 'RMID' === \substr($data, 8, 4)) {
            $offset = 12;
            $limit = \strlen($data);

            while ($offset + 8 <= $limit) {
                $id = \substr($data, $offset, 4);
                $length = $this->uint32le($data, $offset + 4);

                if (null === $length || $offset + 8 + $length > $limit) {
                    return null;
                }

                if ('data' === $id) {
                    return \substr($data, $offset + 8, $length);
                }

                $offset += 8 + $length + ($length % 2);
            }

            return null;
        }

        return $data;
    }
}
