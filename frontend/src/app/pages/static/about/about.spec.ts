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
        expect(fixture.nativeElement.querySelectorAll('img')).toHaveLength(4);
    });
});
