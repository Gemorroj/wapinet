import { parseAudioFileName } from './filename';

describe('parseAudioFileName', () => {
    it('parses artist and title', () => {
        expect(parseAudioFileName('Artist - Title.mp3')).toEqual({ artist: 'Artist', title: 'Title' });
    });

    it('parses a leading track number', () => {
        expect(parseAudioFileName('01 - Artist - Title.flac')).toEqual({
            artist: 'Artist',
            trackNumber: '01',
            title: 'Title',
        });
    });

    it('parses artist, album and title', () => {
        expect(parseAudioFileName('Artist - Album - Title.ogg')).toEqual({
            artist: 'Artist',
            album: 'Album',
            title: 'Title',
        });
    });

    it('parses track in the middle', () => {
        expect(parseAudioFileName('Artist - Album - 07 - Title.mp3')).toEqual({
            artist: 'Artist',
            album: 'Album',
            trackNumber: '07',
            title: 'Title',
        });
    });

    it('uses underscores as separators and keeps a title without dashes', () => {
        expect(parseAudioFileName('Artist_-_Some_Title.mp3')).toEqual({ artist: 'Artist', title: 'Some Title' });
        expect(parseAudioFileName('Just a title.mp3')).toEqual({ title: 'Just a title' });
    });
});
