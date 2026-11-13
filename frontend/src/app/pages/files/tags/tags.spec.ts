import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { TagsApi } from '../../../core/tags-api';
import { Tags } from './tags';

describe('Tags', () => {
    const list = vi.fn().mockResolvedValue({
        items: [{ id: 1, name: 'php', count: 5 }],
        total: 1,
        page: 1,
        perPage: 50,
    });
    const params = new BehaviorSubject(convertToParamMap({}));
    const route = {
        queryParamMap: params.asObservable(),
        snapshot: { queryParamMap: convertToParamMap({}) },
    };

    beforeEach(async () => {
        list.mockClear();
        params.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [Tags],
            providers: [{ provide: TagsApi, useValue: { list } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('loads and renders tags', async () => {
        const fixture = TestBed.createComponent(Tags);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith({ page: 1, perPage: 50, filter: { q: undefined } });
        expect(fixture.nativeElement.textContent).toContain('php');
        expect(fixture.nativeElement.textContent).toContain('5');
    });

    it('loads the page from the query string', async () => {
        params.next(convertToParamMap({ page: '3' }));

        const fixture = TestBed.createComponent(Tags);

        await fixture.whenStable();

        expect(list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 3 }));
    });

    it('loads the search query from the query string', async () => {
        params.next(convertToParamMap({ q: 'php' }));

        const fixture = TestBed.createComponent(Tags);

        await fixture.whenStable();

        expect(list).toHaveBeenLastCalledWith(expect.objectContaining({ filter: { q: 'php' } }));
    });
});
