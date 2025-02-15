<?php

namespace App\Service;

use PhpTabs\PhpTabs;

final readonly class Midi
{
    public function getDuration(string $filename): float
    {
        // https://github.com/stdtabs/phptabs/issues/20

        $song = new PhpTabs($filename);
        $total = 0.0;

        // Take all measures for the first track
        $measures = $song->getTablature()->getSong()->getTrack(0)->getMeasures();

        // Sum durations for each measure
        foreach ($measures as $measure) {
            // Calculate duration in seconds
            $total += 60
                * $measure->getTimeSignature()->getNumerator()
                / $measure->getTempo()->getValue();
        }

        return $total;
    }
}
