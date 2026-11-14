import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { OpenSource } from './open-source';

describe('OpenSource', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [OpenSource],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders open source projects', () => {
        const fixture = TestBed.createComponent(OpenSource);

        fixture.detectChanges();

        const links = fixture.nativeElement.querySelectorAll('a');

        expect(fixture.nativeElement.textContent).toContain('Archive 7z');
        expect(fixture.nativeElement.textContent).toContain('Rss');
        expect(fixture.nativeElement.textContent).toContain('PHP Obfuscator');
        expect(fixture.nativeElement.textContent).toContain('Все проекты на GitHub');
        expect(links).toHaveLength(16);
        expect(links[1].getAttribute('href')).toBe('https://github.com/Gemorroj/wapinet');
        expect(links[1].getAttribute('target')).toBe('_blank');
    });
});
