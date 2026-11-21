import { AfterViewInit, Directive, ElementRef, OnDestroy, inject } from '@angular/core';

/**
 * Подсветка <pre><code> в HTML из [innerHTML]. Для одной строки кода — app-code-block.
 */
@Directive({
    selector: '[appCodeHighlight]',
})
export class CodeHighlightDirective implements AfterViewInit, OnDestroy {
    private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);
    private observer?: MutationObserver;
    private highlighting = false;

    ngAfterViewInit(): void {
        void this.highlight();

        this.observer = new MutationObserver(() => {
            if (!this.highlighting) {
                void this.highlight();
            }
        });

        this.observer.observe(this.host.nativeElement, {
            childList: true,
            subtree: true,
        });
    }

    ngOnDestroy(): void {
        this.observer?.disconnect();
    }

    private async highlight(): Promise<void> {
        const hasCode = this.host.nativeElement.querySelector('pre code');

        if (!hasCode) {
            return;
        }

        this.highlighting = true;

        try {
            const { default: hljs } = await import('highlight.js/lib/common');
            const blocks = this.host.nativeElement.querySelectorAll<HTMLElement>('pre code');

            for (const block of blocks) {
                if (block.classList.contains('hljs') || !block.isConnected) {
                    continue;
                }

                try {
                    hljs.highlightElement(block);
                } catch {
                    // Unknown language: leave the block as plain escaped text.
                }
            }
        } finally {
            this.highlighting = false;
        }
    }
}
