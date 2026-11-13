import { Component, OnDestroy, inject, input, signal } from '@angular/core';
import { TuiButton, TuiIcon, TuiTitle } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { copyText } from '../../core/clipboard';
import { saveBlob } from '../../core/download';

@Component({
    imports: [TuiButton, TuiCardLarge, TuiIcon, TuiTitle],
    selector: 'app-tool-result',
    styleUrl: './tool-result.css',
    templateUrl: './tool-result.html',
})
export class ToolResult implements OnDestroy {
    readonly title = input('Результат');
    readonly text = input<string | null>(null);
    readonly filename = input('result.txt');
    readonly mimeType = input('text/plain;charset=utf-8');

    protected readonly copied = signal(false);
    private timer: ReturnType<typeof setTimeout> | null = null;

    protected async copy(): Promise<void> {
        const text = this.text();

        if (!text) {
            return;
        }

        this.copied.set(await copyText(text));

        if (null !== this.timer) {
            clearTimeout(this.timer);
        }

        this.timer = setTimeout(() => this.copied.set(false), 2000);
    }

    protected download(): void {
        const text = this.text();

        if (!text) {
            return;
        }

        saveBlob(new Blob([text], { type: this.mimeType() }), this.filename());
    }

    ngOnDestroy(): void {
        if (null !== this.timer) {
            clearTimeout(this.timer);
        }
    }
}
