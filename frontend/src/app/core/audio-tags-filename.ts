export interface ParsedAudioFileName {
    artist?: string;
    album?: string;
    title?: string;
    trackNumber?: string;
}

export function parseAudioFileName(fileName: string): ParsedAudioFileName {
    const base = fileName
        .replace(/\.[^.]+$/, '')
        .replace(/_/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    if ('' === base) {
        return {};
    }

    let rest = base;
    let trackNumber: string | undefined;
    const trackMatch = rest.match(/^(\d{1,3})[.)\s-]+(.+)$/);

    if (trackMatch) {
        trackNumber = trackMatch[1];
        rest = trackMatch[2].trim();
    }

    const parts = rest
        .split(/\s+-\s+/)
        .map((part) => part.trim())
        .filter(Boolean);

    if (parts.length >= 4) {
        if (/^\d{1,3}$/.test(parts[2])) {
            return {
                artist: parts[0],
                album: parts[1],
                trackNumber: parts[2],
                title: parts.slice(3).join(' - '),
            };
        }

        return {
            artist: parts[0],
            album: parts[1],
            title: parts.slice(2).join(' - '),
            ...(trackNumber ? { trackNumber } : {}),
        };
    }

    if (3 === parts.length) {
        return {
            artist: parts[0],
            album: parts[1],
            title: parts[2],
            ...(trackNumber ? { trackNumber } : {}),
        };
    }

    if (2 === parts.length) {
        return {
            artist: parts[0],
            title: parts[1],
            ...(trackNumber ? { trackNumber } : {}),
        };
    }

    return {
        title: parts[0] ?? '',
        ...(trackNumber ? { trackNumber } : {}),
    };
}
