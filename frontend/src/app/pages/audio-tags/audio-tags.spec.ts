import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Data, ParamMap, Router, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { AudioTagsInfoResponse } from '../../api/generated/models';
import { AudioTagsApi } from '../../core/api/audio-tags-api';
import { AudioTags } from './audio-tags';

describe('AudioTags', () => {
    const show = vi.fn();
    const create = vi.fn();
    const update = vi.fn();
    const remove = vi.fn();
    const clear = vi.fn();
    const api = {
        show,
        create,
        update,
        clear,
        delete: remove,
        coverUrl: (id: string) => `/api/audio-tags/${id}/cover`,
        downloadUrl: (id: string) => `/api/audio-tags/${id}/download`,
    };
    let routeData: BehaviorSubject<Data>;
    let paramMap: BehaviorSubject<ParamMap>;
    let router: Router;

    const info: AudioTagsInfoResponse = {
        id: 'a'.repeat(32),
        originalName: 'sample.mp3',
        size: 1024,
        duration: 180,
        format: 'mp3',
        bitrate: 128000,
        bitrateMode: 'cbr',
        sampleRate: 44100,
        channelMode: 'stereo',
        tagFormats: ['id3v1', 'id3v2.4'],
        canWrite: true,
        otherTags: [],
        tags: {
            title: 'Title',
            albumArtist: 'Album Artist',
            artist: 'Artist',
            album: 'Album',
            year: '2026',
            trackNumber: '3',
            urlUser: 'https://example.com',
            genre: 'Rock',
            comment: 'Comment',
            composer: 'Composer',
            discNumber: '1',
            copyright: 'Copyright',
            lyrics: 'Lyrics',
            bpm: '120',
            isrc: 'US-XXX-26-00001',
            hasPicture: true,
        },
    };

    beforeEach(async () => {
        show.mockReset();
        create.mockReset();
        update.mockReset();
        clear.mockReset();
        remove.mockReset();
        routeData = new BehaviorSubject<Data>({ tab: 'tool' });
        paramMap = new BehaviorSubject<ParamMap>(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [AudioTags],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: AudioTagsApi, useValue: api },
                { provide: ActivatedRoute, useValue: { data: routeData, paramMap } },
            ],
        }).compileComponents();

        router = TestBed.inject(Router);
        vi.spyOn(router, 'navigate').mockResolvedValue(true);
        vi.spyOn(router, 'navigateByUrl').mockResolvedValue(true);
    });

    it('loads a saved file from the edit link', async () => {
        show.mockResolvedValue(info);

        const fixture = TestBed.createComponent(AudioTags);

        fixture.detectChanges();
        paramMap.next(convertToParamMap({ id: info.id }));
        await fixture.whenStable();
        fixture.detectChanges();

        expect(show).toHaveBeenCalledWith(info.id);
        expect((fixture.componentInstance as unknown as { title: { (): string } }).title()).toBe('Title');
        expect((fixture.nativeElement.querySelector('input[name="title"]') as HTMLInputElement).value).toBe('Title');
    });

    it('uploads a source file and fills the form', async () => {
        create.mockResolvedValue(info);

        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            sourceFiles: { set(value: File[]): void };
            create(): Promise<void>;
        };
        const file = new File(['audio'], 'sample.mp3', { type: 'audio/mpeg' });

        fixture.detectChanges();
        component.sourceFiles.set([file]);
        await component.create();
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(create).toHaveBeenCalledWith(file, '');
        expect(router.navigate).toHaveBeenCalledWith(['/audio_tags', info.id], { replaceUrl: true });
        expect((fixture.componentInstance as unknown as { title: { (): string } }).title()).toBe('Title');
        expect((fixture.nativeElement.querySelector('input[name="title"]') as HTMLInputElement).value).toBe('Title');
        expect((fixture.nativeElement.querySelector('input[name="albumArtist"]') as HTMLInputElement).value).toBe('Album Artist');
        expect(fixture.nativeElement.textContent).toContain('id3v2.4');
        expect(fixture.nativeElement.textContent).toContain('3:00');
        expect(fixture.nativeElement.querySelector('.audio-tags__cover')?.getAttribute('src')).toContain('/api/audio-tags/');
    });

    it('shows the server reason for read-only files', () => {
        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            info: { set(value: AudioTagsInfoResponse): void };
        };

        fixture.detectChanges();
        component.info.set({
            ...info,
            canWrite: false,
            readOnlyReason: 'Это WAV-файл. Запись тегов в WAV не поддерживается.',
            tagFormats: [],
        });
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Это WAV-файл. Запись тегов в WAV не поддерживается.');
    });

    it('updates tags and cover settings', async () => {
        update.mockResolvedValue(info);

        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            info: { set(value: AudioTagsInfoResponse): void };
            title: { set(value: string): void };
            removeOtherTags: { set(value: boolean): void };
            update(): Promise<void>;
        };

        fixture.detectChanges();
        component.info.set(info);
        component.title.set('New title');
        component.removeOtherTags.set(true);
        await component.update();
        fixture.detectChanges();

        expect(update).toHaveBeenCalledWith(info.id, {
            title: 'New title',
            albumArtist: '',
            artist: '',
            album: '',
            year: '',
            trackNumber: '',
            urlUser: '',
            genre: '',
            comment: '',
            composer: '',
            discNumber: '',
            copyright: '',
            lyrics: '',
            bpm: '',
            isrc: '',
            removeOtherTags: true,
            removePicture: false,
            picture: null,
            pictureUrl: '',
        });
        expect(fixture.nativeElement.textContent).toContain('Теги сохранены.');
    });

    it('filters genre suggestions by the typed text', () => {
        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            genreQuery: { set(value: string): void };
            genreSuggestions: { (): string[] };
        };

        fixture.detectChanges();
        component.genreQuery.set('rock');

        expect(component.genreSuggestions()).toContain('Rock');
        expect(component.genreSuggestions()).not.toContain('Pop');
    });

    it('fills empty tags from the file name', async () => {
        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            info: { set(value: AudioTagsInfoResponse): void };
            title: { (): string };
            artist: { (): string };
            album: { (): string };
            trackNumber: { (): string };
            fillFromFilename(): void;
        };

        fixture.detectChanges();
        component.info.set({ ...info, originalName: 'Artist - Album - 07 - Title.mp3' });
        component.fillFromFilename();
        fixture.detectChanges();

        expect(component.title()).toBe('Title');
        expect(component.artist()).toBe('Artist');
        expect(component.album()).toBe('Album');
        expect(component.trackNumber()).toBe('07');
        expect(fixture.nativeElement.textContent).toContain('Заполнено из имени файла');
    });

    it('shows other tags and clears all tags after confirmation', async () => {
        clear.mockResolvedValue({ ...info, tags: { hasPicture: false }, otherTags: [] });

        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            info: { set(value: AudioTagsInfoResponse): void };
            pendingClear: { set(value: boolean): void };
            clearTags(): Promise<void>;
        };

        fixture.detectChanges();
        component.info.set({ ...info, otherTags: [{ name: 'custom', value: 'value' }] });
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Другие теги');
        expect(fixture.nativeElement.textContent).toContain('custom');

        component.pendingClear.set(true);
        await component.clearTags();
        fixture.detectChanges();

        expect(clear).toHaveBeenCalledWith(info.id);
        expect(fixture.nativeElement.textContent).toContain('Все теги удалены.');
    });

    it('deletes the temporary file when starting over', async () => {
        remove.mockResolvedValue(undefined);

        const fixture = TestBed.createComponent(AudioTags);
        const component = fixture.componentInstance as unknown as {
            info: { set(value: AudioTagsInfoResponse): void };
            reset(): Promise<void>;
        };

        fixture.detectChanges();
        component.info.set(info);
        await component.reset();

        expect(remove).toHaveBeenCalledWith(info.id);
        expect(fixture.nativeElement.textContent).toContain('Аудиофайл');
    });
});
