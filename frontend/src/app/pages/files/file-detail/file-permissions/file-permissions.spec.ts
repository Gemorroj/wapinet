import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { FilePermissions } from './file-permissions';

describe('FilePermissions', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [FilePermissions],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('shows risky permissions and collapses the rest', () => {
        const fixture = TestBed.createComponent(FilePermissions);

        fixture.componentRef.setInput('permissions', [
            { name: 'READ', description: 'Read files', danger: true, warning: false, cost: false },
            { name: 'INTERNET', description: 'Open sockets', danger: false, warning: false, cost: false },
            { name: 'SEND_SMS', description: '', danger: false, warning: false, cost: true },
        ]);
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('READ');
        expect(text).toContain('Опасно');
        expect(text).toContain('SEND_SMS');
        expect(text).toContain('Платно');
        expect(text).toContain('Остальные разрешения (1)');
        expect(fixture.nativeElement.querySelectorAll('.file-permissions__permission').length).toBe(2);
        expect(fixture.nativeElement.querySelector('.file-permissions__compact li')?.textContent).toContain('INTERNET');
    });
});
