import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { BbcodeApi } from '../../core/bbcode-api';
import { BbcodeEditor } from './bbcode-editor';

describe('BbcodeEditor', () => {
    const preview = vi.fn().mockResolvedValue({ html: '<strong class="bb">жирный</strong>' });

    beforeEach(async () => {
        preview.mockClear();

        await TestBed.configureTestingModule({
            imports: [BbcodeEditor],
            providers: [{ provide: BbcodeApi, useValue: { preview } }, provideTaiga()],
        }).compileComponents();
    });

    it('renders BBCode preview', async () => {
        const fixture = TestBed.createComponent(BbcodeEditor);

        fixture.componentRef.setInput('label', 'Сообщение');
        fixture.componentRef.setInput('name', 'message');
        fixture.componentRef.setInput('value', '[b]жирный[/b]');
        fixture.componentRef.setInput('placeholder', 'Напишите сообщение…');
        await fixture.whenStable();
        fixture.detectChanges();

        const textarea = fixture.nativeElement.querySelector('textarea[name="message"]') as HTMLTextAreaElement;

        expect(textarea.value).toBe('[b]жирный[/b]');
        expect(textarea.placeholder).toBe('Напишите сообщение…');

        const previewButton = [...fixture.nativeElement.querySelectorAll('button')].find((button: HTMLButtonElement) =>
            button.textContent?.includes('Предпросмотр'),
        ) as HTMLButtonElement;

        previewButton.click();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(preview).toHaveBeenCalledWith('[b]жирный[/b]');
        expect(fixture.nativeElement.querySelector('.bbcode-editor__preview')?.textContent).toContain('жирный');
        expect(fixture.nativeElement.querySelector('textarea[name="message"]')).toBeNull();

        fixture.destroy();
    });
});
