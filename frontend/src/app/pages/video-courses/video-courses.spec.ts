import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { VideoCourses } from './video-courses';

describe('VideoCourses', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [VideoCourses],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders video course channels', () => {
        const fixture = TestBed.createComponent(VideoCourses);

        fixture.detectChanges();

        const links = fixture.nativeElement.querySelectorAll('a');

        expect(fixture.nativeElement.textContent).toContain('OTUS');
        expect(links).toHaveLength(9);
        expect(links[1].getAttribute('href')).toBe('https://vkvideo.ru/@otusru');
        expect(links[1].getAttribute('target')).toBe('_blank');
    });
});
