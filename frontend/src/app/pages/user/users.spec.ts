import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { UsersApi } from '../../core/api/users-api';
import { Users } from './users';

describe('Users', () => {
    const list = vi.fn().mockResolvedValue({
        items: [
            { id: 1, username: 'alice', avatarUrl: 'https://gravatar.com/avatar/1', isOnline: true },
            { id: 2, username: 'bob', avatarUrl: 'https://gravatar.com/avatar/2', isOnline: false },
        ],
        total: 2,
        page: 1,
        perPage: 20,
    });
    const queryParams = new BehaviorSubject(convertToParamMap({}));
    const route = { queryParamMap: queryParams.asObservable() };

    beforeEach(async () => {
        list.mockClear();
        queryParams.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [Users],
            providers: [{ provide: UsersApi, useValue: { list } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('loads and renders the user list', async () => {
        const fixture = TestBed.createComponent(Users);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith({ filter: { search: undefined }, page: 1 });
        expect(fixture.nativeElement.textContent).toContain('alice');
        expect(fixture.nativeElement.textContent).toContain('bob');
        expect(fixture.nativeElement.textContent).not.toContain('онлайн');
        expect(fixture.nativeElement.querySelectorAll('.online-badge').length).toBe(1);

        fixture.destroy();
    });

    it('passes the search query from the route', async () => {
        queryParams.next(convertToParamMap({ search: 'ali', page: '2' }));

        const fixture = TestBed.createComponent(Users);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith({ filter: { search: 'ali' }, page: 2 });

        fixture.destroy();
    });
});
