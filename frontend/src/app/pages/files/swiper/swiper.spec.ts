import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, ParamMap, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { Subject } from 'rxjs';

import { FileResponse } from '../../../api/generated/models';
import { FilesApi } from '../../../core/api/files-api';
import { FileSwiper } from './swiper';

describe('FileSwiper', () => {
    const file: FileResponse = {
        id: 1,
        name: 'first.jpg',
        description: '',
        descriptionHtml: '',
        size: 1024,
        mimeType: 'image/jpeg',
        category: 'image',
        createdAt: '2026-01-01T00:00:00+00:00',
        updatedAt: null,
        lastViewAt: null,
        countViews: 0,
        user: null,
        hasPassword: false,
        hidden: false,
        tags: [],
        meta: null,
        canEdit: false,
        canDelete: false,
        hash: '',
        links: {
            url: '/static/file/first.jpg',
            downloadUrl: '/api/files/1/download',
            thumbnailUrl: null,
            convertedUrl: null,
            convertedFormat: null,
        },
        navigation: {
            prevId: null,
            nextId: 2,
        },
        content: {
            playableAudio: false,
            playableVideo: false,
            conversionPending: false,
            playlist: [],
            torrent: [],
            permissions: [],
        },
    };

    it('hides the previous file while the next one is loading', async () => {
        const params = new Subject<ParamMap>();
        const show = vi
            .fn()
            .mockResolvedValueOnce(file)
            .mockImplementationOnce(() => new Promise<FileResponse>(() => {}));

        await TestBed.configureTestingModule({
            imports: [FileSwiper],
            providers: [
                provideRouter([]),
                provideTaiga(),
                { provide: FilesApi, useValue: { show } },
                { provide: ActivatedRoute, useValue: { paramMap: params.asObservable() } },
            ],
        }).compileComponents();

        const fixture = TestBed.createComponent(FileSwiper);

        fixture.detectChanges();
        params.next(convertToParamMap({ id: '1' }));
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('.swiper__image')).not.toBeNull();

        params.next(convertToParamMap({ id: '2' }));
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('.swiper__image')).toBeNull();
        expect(fixture.nativeElement.querySelector('.swiper__loading')).not.toBeNull();

        fixture.destroy();
    });
});
