import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { Auth } from '../../core/auth/auth';
import { FilesApi } from '../../core/api/files-api';
import { Files } from './files';

describe('Files', () => {
    const item = {
        id: 1,
        name: 'test.txt',
        description: 'Тестовый файл',
        size: 10,
        mimeType: 'text/plain',
        category: 'text',
        createdAt: '2026-01-01T00:00:00+00:00',
        user: null,
        hasPassword: false,
        hidden: false,
        url: '/static/file/test.txt',
        thumbnailUrl: null,
        meta: { width: 1920, height: 1080, duration: 125 },
    };

    const list = vi.fn().mockResolvedValue({ items: [item], total: 1, page: 1, perPage: 20 });
    let admin = false;
    const auth = {
        authenticated: () => false,
        user: () => null,
        isAdmin: () => admin,
    };
    const params = new BehaviorSubject(convertToParamMap({}));
    const route = {
        queryParamMap: params.asObservable(),
        snapshot: {
            queryParamMap: convertToParamMap({}),
            data: {},
        },
    };

    beforeEach(async () => {
        admin = false;
        list.mockClear();
        params.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [Files],
            providers: [
                { provide: FilesApi, useValue: { list } },
                { provide: Auth, useValue: auth },
                provideRouter([]),
                { provide: ActivatedRoute, useValue: route },
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('loads the file list on init', async () => {
        const fixture = TestBed.createComponent(Files);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith({
            page: 1,
            perPage: 20,
            sort: 'newest',
            filter: {
                q: undefined,
                category: undefined,
                date: 'all',
                tag: undefined,
                user: undefined,
                hidden: undefined,
                mine: false,
            },
        });
        expect(fixture.nativeElement.textContent).toContain('test.txt');
        expect(fixture.nativeElement.textContent).toContain('Тестовый файл');
        expect(fixture.nativeElement.textContent).toContain('1920×1080');
        expect(fixture.nativeElement.textContent).toContain('2:05');
    });

    it('loads the page from the query string', async () => {
        params.next(convertToParamMap({ page: '3' }));

        const fixture = TestBed.createComponent(Files);

        await fixture.whenStable();

        expect(list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 3 }));
    });

    it('does not open the filters drawer for query filters', async () => {
        params.next(convertToParamMap({ category: 'video' }));

        const fixture = TestBed.createComponent(Files);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('tui-drawer')).toBeNull();
    });

    it('closes the filters drawer on escape', async () => {
        const fixture = TestBed.createComponent(Files);

        await fixture.whenStable();
        fixture.detectChanges();

        const button = fixture.nativeElement.querySelector('button[aria-label="Фильтры"]') as HTMLButtonElement;

        button.click();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('tui-drawer')).not.toBeNull();

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('tui-drawer')).toBeNull();
    });

    it('shows the moderation counter to admins', async () => {
        admin = true;

        const fixture = TestBed.createComponent(Files);
        const component = fixture.componentInstance as unknown as { loadHiddenCount(): Promise<void> };

        await component.loadHiddenCount();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith({ page: 1, perPage: 1, filter: { hidden: true } });
        expect(fixture.nativeElement.textContent).toContain('Файлов на модерации: 1');
        expect(fixture.nativeElement.querySelector('a[href="/files/hidden"]')).not.toBeNull();
    });
});
