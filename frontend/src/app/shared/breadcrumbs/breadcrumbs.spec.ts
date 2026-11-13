import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { Breadcrumbs } from './breadcrumbs';

describe('Breadcrumbs', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Breadcrumbs],
            providers: [provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders links for parents and text for the current page', () => {
        const fixture = TestBed.createComponent(Breadcrumbs);

        fixture.componentRef.setInput('items', [{ label: 'Главная', link: '/' }, { label: 'Файлы', link: '/files' }, { label: 'Теги' }]);
        fixture.detectChanges();

        const links = fixture.nativeElement.querySelectorAll('a');

        expect(links).toHaveLength(2);
        expect(links[0].getAttribute('href')).toBe('/');
        expect(links[1].getAttribute('href')).toBe('/files');
        expect(fixture.nativeElement.textContent).toContain('Теги');
    });
});
