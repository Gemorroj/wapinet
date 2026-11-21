import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { HomeApi } from '../../core/api/home-api';
import { Home } from './home';

describe('Home', () => {
    const stats = vi.fn().mockResolvedValue({
        files: 12,
        gists: 34,
        guestbook: 7538,
        online: 3,
        newsLastDate: '2026-01-01T00:00:00+00:00',
        forumTopics: 1670,
        forumPosts: 24027,
    });

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Home],
            providers: [{ provide: HomeApi, useValue: { stats } }, provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders navigation groups, key links and counters', async () => {
        const fixture = TestBed.createComponent(Home);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(fixture.nativeElement.querySelectorAll('h2')).toHaveLength(5);
        expect((fixture.nativeElement.querySelectorAll('.home__icon') as NodeListOf<HTMLElement>).length).toBeGreaterThanOrEqual(20);
        expect(fixture.nativeElement.querySelector('audio')?.getAttribute('src')).toContain('Aleksandr_Sklyar');
        expect(fixture.nativeElement.querySelector('audio')?.getAttribute('title')).toContain('Ты забыл, брат');
        expect(text).toContain('Файлообменник');
        expect(text).toContain('Инструменты текста');
        expect(text).toContain('символы, эмодзи и их коды');
        expect(text).toContain('Код');
        expect(text).toContain('Сеть');
        expect(text).toContain('HTTP-клиент');
        expect(text).toContain('PHP-валидатор');
        expect(text).toContain('Материалы');
        expect(text).toContain('Политика');
        expect(text).toContain('12');
        expect(text).toContain('34');
        expect(text).toContain('7538');
        expect(fixture.nativeElement.querySelectorAll('a').length).toBeGreaterThanOrEqual(20);
    });
});
