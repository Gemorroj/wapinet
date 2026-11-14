import { WritableSignal, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { App } from './app';
import { Auth } from './core/auth/auth';
import { HomeApi } from './core/api/home-api';

describe('App', () => {
    let authenticated: WritableSignal<boolean>;
    let stats: ReturnType<typeof vi.fn>;

    beforeEach(async () => {
        authenticated = signal(false);
        stats = vi.fn().mockResolvedValue({
            files: 12,
            gists: 34,
            guestbook: 7538,
            online: 5,
            newsLastDate: null,
            forumTopics: 1670,
            forumPosts: 24027,
        });

        await TestBed.configureTestingModule({
            imports: [App],
            providers: [
                { provide: Auth, useValue: { authenticated, user: signal(null), logout: vi.fn() } },
                { provide: HomeApi, useValue: { stats } },
                provideRouter([]),
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('renders main navigation and the online counter', async () => {
        const fixture = TestBed.createComponent(App);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(fixture.nativeElement.querySelectorAll('.app-nav a')).toHaveLength(6);
        expect(fixture.nativeElement.querySelector('.app-ribbon img')?.getAttribute('src')).toBe('/resources/georg_lent_200.png');
        expect(text).toContain('Файлообменник');
        expect(text).toContain('Форум');
        expect(text).toContain('Онлайн: 5');
    });
});
