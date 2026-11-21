export interface MediaRow {
    readonly label: string;
    readonly source: string;
    readonly value: string;
}

export interface MediaInfo {
    readonly audio: MediaRow[];
    readonly drm: MediaRow[];
    readonly video: MediaRow[];
}

interface Codec {
    readonly label: string;
    readonly mime: string;
}

interface DrmSystem {
    readonly keySystems: readonly string[];
    readonly label: string;
}

const VIDEO_CODECS: readonly Codec[] = [
    { label: 'MP4 (H.264)', mime: 'video/mp4; codecs="avc1.42E01E"' },
    { label: 'MP4 (HEVC)', mime: 'video/mp4; codecs="hvc1.1.6.L93.B0"' },
    { label: 'MP4 (AV1)', mime: 'video/mp4; codecs="av01.0.05M.08"' },
    { label: 'WebM (VP8)', mime: 'video/webm; codecs="vp8"' },
    { label: 'WebM (VP9)', mime: 'video/webm; codecs="vp9"' },
    { label: 'WebM (AV1)', mime: 'video/webm; codecs="av01.0.05M.08"' },
    { label: 'HLS', mime: 'application/vnd.apple.mpegurl' },
    { label: 'DASH', mime: 'application/dash+xml' },
];

const AUDIO_CODECS: readonly Codec[] = [
    { label: 'AAC', mime: 'audio/mp4; codecs="mp4a.40.2"' },
    { label: 'MP3', mime: 'audio/mpeg' },
    { label: 'Opus', mime: 'audio/webm; codecs="opus"' },
    { label: 'Vorbis', mime: 'audio/ogg; codecs="vorbis"' },
    { label: 'FLAC', mime: 'audio/flac' },
    { label: 'ALAC', mime: 'audio/mp4; codecs="alac"' },
    { label: 'AC-3', mime: 'audio/mp4; codecs="ac-3"' },
    { label: 'E-AC-3', mime: 'audio/mp4; codecs="ec-3"' },
    { label: 'WAV', mime: 'audio/wav' },
];

const DRM_SYSTEMS: readonly DrmSystem[] = [
    { keySystems: ['com.widevine.alpha'], label: 'Widevine' },
    { keySystems: ['com.microsoft.playready', 'com.microsoft.playready.recommendation'], label: 'PlayReady' },
    { keySystems: ['com.apple.fps', 'com.apple.fps.1_0'], label: 'FairPlay' },
    { keySystems: ['org.w3.clearkey'], label: 'Clear Key' },
];

const DRM_CONFIG: MediaKeySystemConfiguration[] = [
    {
        initDataTypes: ['cenc'],
        videoCapabilities: [{ contentType: 'video/mp4; codecs="avc1.42E01E"' }],
    },
];

export function supportLabel(result: string | null | undefined): string {
    if ('probably' === result) {
        return 'Да';
    }

    if ('maybe' === result) {
        return 'Возможно';
    }

    return 'Нет';
}

export async function mediaSupport(): Promise<MediaInfo> {
    return {
        audio: audioRows(),
        drm: await drmRows(),
        video: videoRows(),
    };
}

export function videoRows(canPlay: (mime: string) => string = canPlayKind('video')): MediaRow[] {
    const rows = VIDEO_CODECS.map((codec) => ({
        label: codec.label,
        source: `HTMLVideoElement.canPlayType('${codec.mime}')`,
        value: supportLabel(canPlay(codec.mime)),
    }));

    return [...rows, mseRow()];
}

export function audioRows(canPlay: (mime: string) => string = canPlayKind('audio')): MediaRow[] {
    return AUDIO_CODECS.map((codec) => ({
        label: codec.label,
        source: `HTMLAudioElement.canPlayType('${codec.mime}')`,
        value: supportLabel(canPlay(codec.mime)),
    }));
}

export async function drmRows(requestAccess: typeof navigator.requestMediaKeySystemAccess | null = browserAccess()): Promise<MediaRow[]> {
    if (null === requestAccess) {
        return [{ label: 'Encrypted Media Extensions', source: 'navigator.requestMediaKeySystemAccess', value: 'Нет' }];
    }

    const systems = await Promise.all(
        DRM_SYSTEMS.map(async (system) => ({
            label: system.label,
            source: `navigator.requestMediaKeySystemAccess(${system.keySystems.map((keySystem) => `'${keySystem}'`).join(', ')})`,
            value: (await isKeySystemSupported(requestAccess, system.keySystems)) ? 'Да' : 'Нет',
        })),
    );

    return [{ label: 'Encrypted Media Extensions', source: 'navigator.requestMediaKeySystemAccess', value: 'Да' }, ...systems];
}

function canPlayKind(kind: 'audio' | 'video'): (mime: string) => string {
    return (mime) => {
        try {
            const element = document.createElement(kind);

            return 'function' === typeof element.canPlayType ? String(element.canPlayType(mime)) : '';
        } catch {
            return '';
        }
    };
}

function mseRow(): MediaRow {
    let supported = false;

    try {
        supported = 'MediaSource' in window && 'function' === typeof MediaSource.isTypeSupported;
    } catch {
        supported = false;
    }

    return {
        label: 'MediaSource Extensions',
        source: 'MediaSource.isTypeSupported()',
        value: supported ? 'Да' : 'Нет',
    };
}

function browserAccess(): typeof navigator.requestMediaKeySystemAccess | null {
    return 'function' === typeof navigator.requestMediaKeySystemAccess ? navigator.requestMediaKeySystemAccess.bind(navigator) : null;
}

async function isKeySystemSupported(requestAccess: typeof navigator.requestMediaKeySystemAccess, keySystems: readonly string[]): Promise<boolean> {
    for (const keySystem of keySystems) {
        try {
            await requestAccess(keySystem, DRM_CONFIG);

            return true;
        } catch {
            // The browser does not know this key system, try the next one.
        }
    }

    return false;
}
