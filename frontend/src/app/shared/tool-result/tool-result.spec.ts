import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { ToolResult } from './tool-result';

describe('ToolResult', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [ToolResult],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders the result and disables actions without text', () => {
        const fixture = TestBed.createComponent(ToolResult);

        fixture.componentRef.setInput('title', 'Результат проверки');
        fixture.componentRef.setInput('text', 'OK');
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Результат проверки');
        expect(fixture.nativeElement.textContent).toContain('Копировать');
        expect(fixture.nativeElement.querySelectorAll('button:disabled').length).toBe(0);

        fixture.componentRef.setInput('text', null);
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelectorAll('button:disabled').length).toBe(2);
    });
});
