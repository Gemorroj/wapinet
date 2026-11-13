import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { Forbidden } from './forbidden/forbidden';
import { NotFound } from './not-found/not-found';

describe('error pages', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders not found page', () => {
        const fixture = TestBed.createComponent(NotFound);

        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Страница не найдена');
        expect(fixture.nativeElement.querySelector('a')?.getAttribute('href')).toBe('/');
    });

    it('renders forbidden page', () => {
        const fixture = TestBed.createComponent(Forbidden);

        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Доступ запрещён');
        expect(fixture.nativeElement.querySelector('a')?.getAttribute('href')).toBe('/');
    });
});
