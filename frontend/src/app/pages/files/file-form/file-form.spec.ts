import { Component } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { FileResponse } from '../../../api/generated/models';
import { AppConfigService } from '../../../core/app-config';
import { Auth } from '../../../core/auth';
import { FilesApi } from '../../../core/files-api';
import { TagsApi } from '../../../core/tags-api';
import { FileForm } from './file-form';

@Component({ template: '' })
class Stub {}

describe('FileForm', () => {
    const file: FileResponse = {
        id: 5,
        name: 'cover-demo.mp3',
        description: 'Демо: аудио с обложкой',
        descriptionHtml: '<p>Демо: аудио с обложкой</p>',
        size: 11059243,
        mimeType: 'audio/mpeg',
        category: 'audio',
        createdAt: '2026-01-01T00:00:00+00:00',
        updatedAt: null,
        lastViewAt: null,
        countViews: 1,
        user: null,
        hasPassword: false,
        hidden: false,
        tags: ['audio', 'demo'],
        meta: null,
        canEdit: true,
        canDelete: true,
        hash: 'hash',
        links: {
            url: '/static/file/cover-demo.mp3',
            downloadUrl: '/api/files/5/download',
            thumbnailUrl: null,
            convertedUrl: null,
            convertedFormat: null,
        },
        navigation: { prevId: null, nextId: null },
        content: {
            playableAudio: true,
            playableVideo: false,
            conversionPending: false,
            playlist: [],
            torrent: [],
            permissions: [],
        },
    };
    const show = vi.fn().mockResolvedValue(file);
    const tagList = vi.fn().mockResolvedValue({
        items: [
            { id: 1, name: 'audio', count: 10 },
            { id: 2, name: 'demo', count: 2 },
        ],
        total: 2,
        page: 1,
        perPage: 10,
    });
    const load = vi.fn().mockResolvedValue(undefined);
    const auth = { authenticated: () => true, user: () => ({ username: 'alice' }), isAdmin: () => false };
    const route = { snapshot: { paramMap: convertToParamMap({}) } };

    beforeEach(async () => {
        show.mockClear();
        tagList.mockClear();
        load.mockClear();
        route.snapshot.paramMap = convertToParamMap({});

        await TestBed.configureTestingModule({
            imports: [FileForm],
            providers: [
                { provide: FilesApi, useValue: { show, create: vi.fn(), update: vi.fn() } },
                { provide: TagsApi, useValue: { list: tagList } },
                { provide: AppConfigService, useValue: { config: () => null, load } },
                { provide: Auth, useValue: auth },
                provideRouter([
                    { path: 'files/:id', component: Stub },
                    { path: 'files', component: Stub },
                ]),
                { provide: ActivatedRoute, useValue: route },
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('renders the upload form', async () => {
        const fixture = TestBed.createComponent(FileForm);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Описание');
        expect(fixture.nativeElement.querySelector('form')).not.toBeNull();
        expect(show).not.toHaveBeenCalled();

        fixture.destroy();
    });

    it('shows the current file when editing', async () => {
        route.snapshot.paramMap = convertToParamMap({ id: '5' });

        const fixture = TestBed.createComponent(FileForm);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(show).toHaveBeenCalledWith(5);
        expect(text).toContain('Текущий файл');
        expect(text).toContain('cover-demo.mp3');
        expect((fixture.nativeElement.querySelector('textarea[name="description"]') as HTMLTextAreaElement).value).toBe('Демо: аудио с обложкой');
        expect(fixture.nativeElement.querySelectorAll('tui-input-chip').length).toBe(2);

        fixture.destroy();
    });

    it('loads tag suggestions while typing', async () => {
        const fixture = TestBed.createComponent(FileForm);

        await fixture.whenStable();
        fixture.detectChanges();

        const input = fixture.nativeElement.querySelector('input[name="tags"]') as HTMLInputElement;

        input.value = 'au';
        input.dispatchEvent(new Event('input'));

        await new Promise((resolve) => setTimeout(resolve, 300));

        expect(tagList).toHaveBeenCalledWith({ filter: { q: 'au' }, page: 1, perPage: 10 });

        fixture.destroy();
    });
});
