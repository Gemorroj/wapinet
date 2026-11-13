import { Component, computed, inject, input } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import php from 'highlight.js/lib/languages/php';
import plaintext from 'highlight.js/lib/languages/plaintext';
import xml from 'highlight.js/lib/languages/xml';

hljs.registerLanguage('json', json);
hljs.registerLanguage('php', php);
hljs.registerLanguage('plaintext', plaintext);
hljs.registerLanguage('xml', xml);

@Component({
    selector: 'app-code-block',
    styleUrl: './code-block.css',
    templateUrl: './code-block.html',
})
export class CodeBlock {
    private readonly sanitizer = inject(DomSanitizer);
    readonly code = input.required<string>();
    readonly language = input<string | null>(null);
    protected readonly highlighted = computed(() => {
        const code = this.code();
        const language = 'html' === this.language() ? 'xml' : (this.language() ?? 'plaintext');

        try {
            return this.sanitizer.bypassSecurityTrustHtml(hljs.highlight(code, { language }).value);
        } catch {
            return this.sanitizer.bypassSecurityTrustHtml(hljs.highlight(code, { language: 'plaintext' }).value);
        }
    });
}
