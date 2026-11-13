import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { UsersApi } from '../../../core/users-api';
import { UserFriends } from './friends';

describe('UserFriends', () => {
    const friends = vi.fn().mockResolvedValue({
        items: [{ id: 2, username: 'bob', avatarUrl: 'https://gravatar.com/avatar/2', isOnline: true }],
        total: 1,
        page: 1,
        perPage: 20,
    });
    const params = new BehaviorSubject(convertToParamMap({ username: 'alice' }));
    const queryParams = new BehaviorSubject(convertToParamMap({}));
    const route = { paramMap: params.asObservable(), queryParamMap: queryParams.asObservable() };

    beforeEach(async () => {
        params.next(convertToParamMap({ username: 'alice' }));
        queryParams.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [UserFriends],
            providers: [{ provide: UsersApi, useValue: { friends } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('loads friends of the routed user', async () => {
        const fixture = TestBed.createComponent(UserFriends);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(friends).toHaveBeenCalledWith('alice', 1);
        expect(fixture.nativeElement.textContent).toContain('alice');
        expect(fixture.nativeElement.textContent).toContain('bob');
        expect(fixture.nativeElement.querySelectorAll('.online-badge').length).toBe(1);

        fixture.destroy();
    });
});
