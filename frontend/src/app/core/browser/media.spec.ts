import { audioRows, drmRows, supportLabel, videoRows } from './media';

describe('supportLabel', () => {
    it('maps canPlayType answers to labels', () => {
        expect(supportLabel('probably')).toBe('Да');
        expect(supportLabel('maybe')).toBe('Возможно');
        expect(supportLabel('')).toBe('Нет');
        expect(supportLabel(undefined)).toBe('Нет');
    });
});

describe('videoRows', () => {
    it('marks codecs supported when canPlayType answers "probably"', () => {
        const rows = videoRows(() => 'probably');
        const h264 = rows.find((row) => 'MP4 (H.264)' === row.label);

        expect(h264?.value).toBe('Да');
        expect(rows.every((row) => 'MediaSource Extensions' === row.label || 'Да' === row.value)).toBe(true);
    });

    it('passes the codec mime type to canPlayType', () => {
        const mimes: string[] = [];

        videoRows((mime) => {
            mimes.push(mime);

            return '';
        });

        expect(mimes).toContain('video/webm; codecs="vp9"');
        expect(mimes).toContain('application/vnd.apple.mpegurl');
    });

    it('reports unsupported codecs', () => {
        expect(videoRows(() => '').every((row) => 'Нет' === row.value || 'MediaSource Extensions' === row.label)).toBe(true);
    });
});

describe('audioRows', () => {
    it('marks every codec unsupported when canPlayType answers nothing', () => {
        expect(audioRows(() => '').every((row) => 'Нет' === row.value)).toBe(true);
    });

    it('keeps the mime type in the source hint', () => {
        const row = audioRows(() => 'maybe').find((item) => 'FLAC' === item.label);

        expect(row?.value).toBe('Возможно');
        expect(row?.source).toContain('audio/flac');
    });
});

describe('drmRows', () => {
    it('returns a single unsupported row without EME', async () => {
        expect(await drmRows(null)).toEqual([{ label: 'Encrypted Media Extensions', source: 'navigator.requestMediaKeySystemAccess', value: 'Нет' }]);
    });

    it('detects supported key systems', async () => {
        const request = vi.fn(async (keySystem: string) => {
            if ('com.widevine.alpha' !== keySystem) {
                throw new Error('unsupported');
            }

            return {} as MediaKeySystemAccess;
        });
        const rows = await drmRows(request as unknown as typeof navigator.requestMediaKeySystemAccess);

        expect(rows[0].value).toBe('Да');
        expect(rows.find((row) => 'Widevine' === row.label)?.value).toBe('Да');
        expect(rows.find((row) => 'PlayReady' === row.label)?.value).toBe('Нет');
    });
});
