import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { CodeField } from './code-field';

describe('CodeField', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [CodeField],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders and updates the value', async () => {
        const fixture = TestBed.createComponent(CodeField);
        let emitted = '';

        fixture.componentRef.setInput('label', 'Код');
        fixture.componentRef.setInput('name', 'code');
        fixture.componentRef.setInput('value', '<?php echo 1;');
        fixture.componentInstance.valueChange.subscribe((value) => (emitted = value));
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const textarea = fixture.nativeElement.querySelector('textarea') as HTMLTextAreaElement;

        expect(textarea.value).toBe('<?php echo 1;');

        textarea.value = '<?php echo 2;';
        textarea.dispatchEvent(new Event('input'));
        fixture.detectChanges();

        expect(emitted).toBe('<?php echo 2;');
    });
});
