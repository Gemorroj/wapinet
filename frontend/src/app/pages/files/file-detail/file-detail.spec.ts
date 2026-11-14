import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, ParamMap, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { Subject, of } from 'rxjs';

import { FileResponse } from '../../../api/generated/models';
import { Auth } from '../../../core/auth/auth';
import { FilesApi } from '../../../core/api/files-api';
import { FileDetail } from './file-detail';

describe('FileDetail', () => {
    const file: FileResponse = {
        id: 1,
        name: 'video.mp4',
        description: 'Тестовое видео',
        descriptionHtml: '<p>Тестовое видео</p>',
        size: 1024,
        mimeType: 'video/mp4',
        category: 'video',
        createdAt: '2026-01-01T00:00:00+00:00',
        updatedAt: null,
        lastViewAt: null,
        countViews: 7,
        user: null,
        hasPassword: false,
        hidden: false,
        tags: [],
        meta: { width: 1920, height: 1080, duration: 125, make: 'Canon' },
        canEdit: false,
        canDelete: false,
        hash: 'abc123',
        links: {
            url: '/static/file/video.mp4',
            downloadUrl: '/api/files/1/download',
            thumbnailUrl: null,
            convertedUrl: null,
            convertedFormat: null,
        },
        navigation: {
            prevId: null,
            nextId: null,
        },
        content: {
            playableAudio: false,
            playableVideo: true,
            conversionPending: false,
            playlist: [],
            torrent: [],
            permissions: [],
        },
    };

    const show = vi.fn().mockResolvedValue(file);
    const archive = vi.fn().mockResolvedValue({ items: [] });

    beforeEach(async () => {
        show.mockResolvedValue(file);
        archive.mockResolvedValue({ items: [] });

        await TestBed.configureTestingModule({
            imports: [FileDetail],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: FilesApi, useValue: { show, archive } },
                { provide: Auth, useValue: { isAdmin: () => false, user: () => null } },
                {
                    provide: ActivatedRoute,
                    useValue: {
                        paramMap: of(convertToParamMap({ id: '1' })),
                        snapshot: { paramMap: convertToParamMap({ id: '1' }) },
                    },
                },
            ],
        }).compileComponents();
    });

    it('renders human-readable metadata', async () => {
        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Разрешение');
        expect(text).toContain('1920×1080');
        expect(text).toContain('Длительность');
        expect(text).toContain('2:05');
        expect(text).toContain('Производитель');
        expect(text).toContain('Canon');
        expect(text).toContain('MD5');
        expect(fixture.nativeElement.querySelectorAll('app-info-hint').length).toBeGreaterThan(0);
    });

    it('renders android permissions', async () => {
        show.mockResolvedValue({
            ...file,
            name: 'app.apk',
            mimeType: 'application/vnd.android.package-archive',
            category: 'android',
            content: {
                ...file.content,
                permissions: [
                    { name: 'READ', description: 'Чтение файлов', danger: true, warning: false, cost: false },
                    { name: 'INTERNET', description: 'Доступ в интернет', danger: false, warning: false, cost: false },
                    { name: 'REQUEST_INSTALL_PACKAGES', description: '', danger: false, warning: false, cost: true },
                ],
            },
        });

        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Права доступа');
        expect(text).toContain('Чтение файлов');
        expect(text).toContain('REQUEST_INSTALL_PACKAGES');
        expect(text).toContain('Опасно');
        expect(text).toContain('Платно');
        expect(text).toContain('Остальные разрешения (1)');
        expect(fixture.nativeElement.querySelectorAll('.file-permissions__permission').length).toBe(2);

        const chips = fixture.nativeElement.querySelectorAll('.file-permissions__compact li') as NodeListOf<HTMLElement>;

        expect(chips.length).toBe(1);
        expect(chips[0].textContent).toContain('INTERNET');
        expect(chips[0].title).toBe('Доступ в интернет');
        expect((fixture.nativeElement.querySelector('.file-permissions__details') as HTMLDetailsElement).open).toBe(false);
    });

    it('links to the previous and next image', async () => {
        show.mockResolvedValue({
            ...file,
            name: 'photo.jpg',
            mimeType: 'image/jpeg',
            category: 'image',
            navigation: { prevId: 5, nextId: 6 },
        });

        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        const links = Array.from(fixture.nativeElement.querySelectorAll('.app-actions a') as NodeListOf<HTMLAnchorElement>).map((link) =>
            link.getAttribute('href'),
        );

        expect(links).toContain('/files/5');
        expect(links).toContain('/files/6');
    });

    it('opens an image preview in the full-screen viewer', async () => {
        show.mockResolvedValue({
            ...file,
            name: 'photo.jpg',
            mimeType: 'image/jpeg',
            category: 'image',
            links: { ...file.links, thumbnailUrl: '/media/photo.jpg' },
        });

        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        const link = fixture.nativeElement.querySelector('.file-detail__preview-link') as HTMLAnchorElement;

        expect(link).not.toBeNull();
        expect(link.getAttribute('href')).toBe('/files/swiper/1');
        expect(fixture.nativeElement.textContent).not.toContain('Слайд');
    });

    it('renders playlist tracks', async () => {
        show.mockResolvedValue({
            ...file,
            name: 'playlist.m3u',
            mimeType: 'audio/x-mpegurl',
            category: 'playlist',
            links: {
                ...file.links,
                url: '/static/file/playlist.m3u',
            },
            content: {
                ...file.content,
                playableVideo: false,
                playlist: [
                    { title: 'Artist - Song', path: 'http://example.com/song.mp3' },
                    { title: 'http://example.com/other.mp3', path: 'http://example.com/other.mp3' },
                ],
            },
        });

        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Содержимое плейлиста');
        expect(text).toContain('Artist - Song');
        expect(text).toContain('http://example.com/song.mp3');
        expect(text).not.toContain('Не удалось прочитать плейлист');
    });

    it('tells the user that the file is being converted and refreshes it', async () => {
        const intervals: Array<() => void> = [];
        const setIntervalSpy = vi.spyOn(globalThis, 'setInterval').mockImplementation(((callback: () => void) => {
            intervals.push(callback);

            return 0 as unknown as ReturnType<typeof setInterval>;
        }) as typeof setInterval);

        try {
            show.mockClear();
            show.mockResolvedValue({
                ...file,
                content: { ...file.content, playableVideo: false, conversionPending: true },
            });

            const fixture = TestBed.createComponent(FileDetail);

            await fixture.whenStable();
            fixture.detectChanges();

            expect(fixture.nativeElement.textContent).toContain('Файл конвертируется в MP4');
            expect(intervals.length).toBeGreaterThan(0);

            show.mockResolvedValue({
                ...file,
                content: { ...file.content, conversionPending: false },
            });

            intervals.at(-1)?.();
            await fixture.whenStable();
            fixture.detectChanges();

            expect(show).toHaveBeenCalledTimes(2);
            expect(fixture.nativeElement.textContent).not.toContain('Файл конвертируется');

            fixture.destroy();
        } finally {
            setIntervalSpy.mockRestore();
        }
    });

    it('reloads the file when navigating between neighbours', async () => {
        const params = new Subject<ParamMap>();

        TestBed.overrideProvider(ActivatedRoute, {
            useValue: {
                paramMap: params.asObservable(),
                snapshot: { paramMap: convertToParamMap({ id: '1' }) },
            },
        });

        show.mockClear();
        show.mockResolvedValue(file);

        const fixture = TestBed.createComponent(FileDetail);

        params.next(convertToParamMap({ id: '1' }));
        await fixture.whenStable();

        show.mockResolvedValue({ ...file, id: 2, name: 'second.png' });
        params.next(convertToParamMap({ id: '2' }));
        await fixture.whenStable();
        fixture.detectChanges();

        expect(show).toHaveBeenLastCalledWith(2);
        expect(fixture.nativeElement.textContent).toContain('second.png');
    });

    it('renders torrent entries', async () => {
        show.mockResolvedValue({
            ...file,
            name: 'data.torrent',
            mimeType: 'application/x-bittorrent',
            category: 'torrent',
            links: {
                ...file.links,
                url: '/static/file/data.torrent',
            },
            content: {
                ...file.content,
                playableVideo: false,
                torrent: [{ path: 'video/movie.mkv', size: 10240 }],
            },
        });

        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Содержимое торрента');
        expect(text).toContain('video/movie.mkv');
        expect(text).toContain('10 КБ');
        expect(text).not.toContain('Не удалось прочитать торрент');
    });

    it('renders archive entries as a tree with a summary', async () => {
        show.mockResolvedValue({
            ...file,
            name: 'archive.zip',
            mimeType: 'application/zip',
            category: 'archive',
            links: {
                ...file.links,
                url: '/static/file/archive.zip',
            },
            content: {
                ...file.content,
                playableVideo: false,
            },
        });
        archive.mockResolvedValue({
            items: [
                { path: 'docs', size: 0, isDirectory: true, isEncrypted: false },
                { path: 'docs/guide.pdf', size: 601, isDirectory: false, isEncrypted: false },
                { path: 'readme.txt', size: 81, isDirectory: false, isEncrypted: false },
            ],
        });

        const fixture = TestBed.createComponent(FileDetail);

        await fixture.whenStable();
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(archive).toHaveBeenCalledWith(1, undefined);
        expect(text).toContain('Содержимое архива');
        expect(text).toContain('2 файла');
        expect(text).toContain('1 папка');
        expect(text).toContain('docs');
        expect(text).toContain('guide.pdf');
        expect(text).toContain('readme.txt');
        expect(fixture.nativeElement.querySelectorAll('.archive-list__row').length).toBe(3);
    });
});
