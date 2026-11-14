import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { AccountNav } from './account-nav';

describe('AccountNav', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [AccountNav],
            providers: [provideRouter([])],
        }).compileComponents();
    });

    it('renders links to every account page', () => {
        const fixture = TestBed.createComponent(AccountNav);
        fixture.detectChanges();

        const links = Array.from(fixture.nativeElement.querySelectorAll('a') as NodeListOf<HTMLAnchorElement>);

        expect(links.map((link) => [link.textContent?.trim(), link.getAttribute('href')])).toEqual([
            ['Профиль', '/user/profile'],
            ['Редактирование', '/user/edit'],
            ['Подписки', '/user/subscriber/edit'],
            ['События', '/user/events'],
        ]);
    });
});
