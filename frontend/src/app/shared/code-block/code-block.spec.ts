import { TestBed } from '@angular/core/testing';

import { CodeBlock } from './code-block';

describe('CodeBlock', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [CodeBlock],
        }).compileComponents();
    });

    it('re-renders when the code changes', () => {
        const fixture = TestBed.createComponent(CodeBlock);

        fixture.componentRef.setInput('code', '{"a":1}');
        fixture.componentRef.setInput('language', 'json');
        fixture.detectChanges();

        const code = fixture.nativeElement.querySelector('code') as HTMLElement;

        expect(code.textContent).toContain('{"a":1}');

        fixture.componentRef.setInput('code', '{"b":2}');
        fixture.detectChanges();

        expect(code.textContent).toContain('{"b":2}');
        expect(code.textContent).not.toContain('"a":1');
    });
});
