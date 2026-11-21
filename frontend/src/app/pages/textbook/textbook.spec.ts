import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { Textbook } from './textbook';

describe('Textbook', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Textbook],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders books and their covers', () => {
        const fixture = TestBed.createComponent(Textbook);

        fixture.detectChanges();

        const links = fixture.nativeElement.querySelectorAll('a');
        const images = fixture.nativeElement.querySelectorAll('img');

        expect(fixture.nativeElement.textContent).toContain('PHP мануал');
        expect(links).toHaveLength(16);
        expect(images.length).toBeGreaterThan(10);
        expect(links[1].getAttribute('href')).toBe('https://php.net/manual/ru/');
    });
});
