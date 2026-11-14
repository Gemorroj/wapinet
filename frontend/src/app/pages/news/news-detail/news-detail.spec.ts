import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { NewsApi } from '../../../core/api/news-api';
import { NewsDetail } from './news-detail';

describe('NewsDetail', () => {
    const show = vi.fn().mockResolvedValue({
        id: 1,
        subject: 'Новость сайта',
        body: 'Текст',
        bodyHtml: '<p>Текст</p>',
        createdAt: '2026-01-01T12:00:00+00:00',
        updatedAt: null,
        createdBy: null,
    });
    const params = new BehaviorSubject(convertToParamMap({ id: '1' }));
    const route = { paramMap: params.asObservable() };

    beforeEach(async () => {
        show.mockClear();
        params.next(convertToParamMap({ id: '1' }));

        await TestBed.configureTestingModule({
            imports: [NewsDetail],
            providers: [{ provide: NewsApi, useValue: { show } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('renders the news item', async () => {
        const fixture = TestBed.createComponent(NewsDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(show).toHaveBeenCalledWith(1);
        expect(fixture.nativeElement.textContent).toContain('Новость сайта');
        expect(fixture.nativeElement.querySelector('.news-detail__content')?.textContent).toContain('Текст');

        fixture.destroy();
    });
});
