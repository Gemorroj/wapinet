import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { Politics } from './politics';

describe('Politics', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Politics],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders political channels', () => {
        const fixture = TestBed.createComponent(Politics);

        fixture.detectChanges();

        const links = fixture.nativeElement.querySelectorAll('a');

        expect(fixture.nativeElement.textContent).toContain('Политика');
        expect(links).toHaveLength(8);
        expect(links[1].getAttribute('href')).toBe('https://t.me/s/ghost_of_novorossia');
        expect(links[1].getAttribute('target')).toBe('_blank');
    });
});
