import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { Auth } from '../../core/auth/auth';
import { GistsApi } from '../../core/api/gists-api';
import { Gists } from './gists';

describe('Gists', () => {
    const list = vi.fn().mockResolvedValue({
        items: [
            {
                id: 1,
                subject: 'Первая запись',
                createdAt: '2026-01-01T12:00:00+00:00',
                updatedAt: null,
                user: { id: 1, username: 'alice' },
                avatarUrl: 'https://gravatar.com/avatar/1',
                isOnline: true,
                canEdit: false,
                canDelete: false,
            },
        ],
        total: 1,
        page: 1,
        perPage: 10,
    });
    const auth = { authenticated: () => false };
    const params = new BehaviorSubject(convertToParamMap({}));
    const queryParams = new BehaviorSubject(convertToParamMap({}));
    const route = {
        snapshot: { data: {} },
        paramMap: params.asObservable(),
        queryParamMap: queryParams.asObservable(),
    };

    beforeEach(async () => {
        list.mockClear();
        params.next(convertToParamMap({}));
        queryParams.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [Gists],
            providers: [
                { provide: GistsApi, useValue: { list } },
                { provide: Auth, useValue: auth },
                provideRouter([]),
                { provide: ActivatedRoute, useValue: route },
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('renders blog posts', async () => {
        const fixture = TestBed.createComponent(Gists);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith({
            filter: { q: null, user: null },
            page: 1,
            sort: 'newest',
        });
        expect(fixture.nativeElement.textContent).toContain('Первая запись');
        expect(fixture.nativeElement.textContent).toContain('alice');

        fixture.destroy();
    });
});
