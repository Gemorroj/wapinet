import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { GistsApi } from '../../../core/api/gists-api';
import { GistDetail } from './gist-detail';

describe('GistDetail', () => {
    const show = vi.fn().mockResolvedValue({
        id: 1,
        subject: 'Первая запись',
        body: '[b]Текст[/b]',
        bodyHtml: '<strong>Текст</strong>',
        createdAt: '2026-01-01T12:00:00+00:00',
        updatedAt: null,
        user: { id: 1, username: 'alice' },
        avatarUrl: 'https://gravatar.com/avatar/1',
        isOnline: false,
        canEdit: true,
        canDelete: true,
    });
    const params = new BehaviorSubject(convertToParamMap({ id: '1' }));
    const route = { paramMap: params.asObservable() };

    beforeEach(async () => {
        show.mockClear();
        params.next(convertToParamMap({ id: '1' }));

        await TestBed.configureTestingModule({
            imports: [GistDetail],
            providers: [{ provide: GistsApi, useValue: { show } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('renders a blog post', async () => {
        const fixture = TestBed.createComponent(GistDetail);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(show).toHaveBeenCalledWith(1);
        expect(fixture.nativeElement.textContent).toContain('Первая запись');
        expect(fixture.nativeElement.querySelector('.gist-detail__content')?.textContent).toContain('Текст');

        fixture.destroy();
    });
});
