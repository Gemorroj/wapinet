import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { UserList } from './user-list';

describe('UserList', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [UserList],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders links to user profiles', () => {
        const fixture = TestBed.createComponent(UserList);

        fixture.componentRef.setInput('items', [
            { id: 1, username: 'alice', avatarUrl: '', isOnline: true },
            { id: 2, username: 'bob', avatarUrl: '', isOnline: false },
        ]);
        fixture.detectChanges();

        const links = Array.from(fixture.nativeElement.querySelectorAll('a') as NodeListOf<HTMLAnchorElement>);

        expect(links.map((link) => link.getAttribute('href'))).toEqual(['/user/profile/alice', '/user/profile/bob']);
        expect(fixture.nativeElement.querySelectorAll('.online-badge').length).toBe(1);
    });

    it('shows the configured empty text', () => {
        const fixture = TestBed.createComponent(UserList);

        fixture.componentRef.setInput('items', []);
        fixture.componentRef.setInput('emptyText', 'Пользователей не найдено.');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Пользователей не найдено.');
    });
});
