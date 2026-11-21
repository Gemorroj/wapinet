import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { InfoHint } from './info-hint';

describe('InfoHint', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [InfoHint],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders an accessible hint button', () => {
        const fixture = TestBed.createComponent(InfoHint);

        fixture.componentRef.setInput('text', 'navigator.userAgent');
        fixture.componentRef.setInput('label', 'Источник данных: User-Agent');
        fixture.detectChanges();

        const button = fixture.nativeElement.querySelector('button') as HTMLButtonElement;

        expect(button.getAttribute('aria-label')).toBe('Источник данных: User-Agent');
    });
});
