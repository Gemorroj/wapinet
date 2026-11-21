import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { CodeSourcePanel } from './code-source';

describe('CodeSourcePanel', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [CodeSourcePanel],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('resolves code from the text field', async () => {
        const fixture = TestBed.createComponent(CodeSourcePanel);

        fixture.componentRef.setInput('label', 'Код');
        fixture.componentRef.setInput('code', '<?php echo 1;');
        fixture.detectChanges();

        await expect(fixture.componentInstance.resolve()).resolves.toEqual({ code: '<?php echo 1;' });
    });

    it('resolves a URL', async () => {
        const fixture = TestBed.createComponent(CodeSourcePanel);
        const component = fixture.componentInstance as unknown as {
            mode: { set(value: 'file'): void };
            url: { set(value: string): void };
        };

        fixture.componentRef.setInput('label', 'Код');
        fixture.componentRef.setInput('code', '');
        component.mode.set('file');
        component.url.set('https://example.com/a.php');

        await expect(fixture.componentInstance.resolve()).resolves.toEqual({ url: 'https://example.com/a.php' });
    });

    it('reads a selected file', async () => {
        const fixture = TestBed.createComponent(CodeSourcePanel);
        const component = fixture.componentInstance as unknown as {
            mode: { set(value: 'file'): void };
            files: { set(value: File[]): void };
        };

        fixture.componentRef.setInput('label', 'Код');
        fixture.componentRef.setInput('code', '');
        component.mode.set('file');
        component.files.set([new File(['<?php echo 1;'], 'a.php', { type: 'text/plain' })]);

        await expect(fixture.componentInstance.resolve()).resolves.toEqual({ code: '<?php echo 1;' });
    });

    it('returns null for an empty source', async () => {
        const fixture = TestBed.createComponent(CodeSourcePanel);

        fixture.componentRef.setInput('label', 'Код');
        fixture.componentRef.setInput('code', '');

        await expect(fixture.componentInstance.resolve()).resolves.toBeNull();
    });
});
