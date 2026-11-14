import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { About } from './about';

describe('About', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [About],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders project links', () => {
        const fixture = TestBed.createComponent(About);

        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('О сайте');
        expect(text).toContain('Symfony');
        expect(text).toContain('jQuery Mobile');
        expect(text).toContain('Angular');
        expect(text).toContain('Taiga UI');
        expect(text).toContain('2006');
        expect(text).toContain('2013');
        expect(text).toContain('2026');
        expect(fixture.nativeElement.querySelector('a[href="/guestbook"]')).not.toBeNull();
    });
});
