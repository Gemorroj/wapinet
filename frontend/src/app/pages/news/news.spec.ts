import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { NewsApi } from '../../core/news-api';
import { News } from './news';

describe('News', () => {
    const list = vi.fn().mockResolvedValue({
        items: [
            {
                id: 1,
                subject: 'Новость сайта',
                createdAt: '2026-01-01T12:00:00+00:00',
                updatedAt: null,
            },
        ],
        total: 1,
        page: 1,
        perPage: 10,
    });
    const queryParams = new BehaviorSubject(convertToParamMap({}));
    const route = { queryParamMap: queryParams.asObservable() };

    beforeEach(async () => {
        list.mockClear();
        queryParams.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [News],
            providers: [{ provide: NewsApi, useValue: { list } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('renders the news list', async () => {
        const fixture = TestBed.createComponent(News);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith(1);
        expect(fixture.nativeElement.textContent).toContain('Новость сайта');

        fixture.destroy();
    });
});
