import { Component, ElementRef, ViewChild, computed, inject, input, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TuiButton, TuiLoader, TuiNotificationDirective, TuiTextfield } from '@taiga-ui/core';
import { TuiTextarea } from '@taiga-ui/kit/components/textarea';

import { BbcodeApi } from '../../core/api/bbcode-api';
import { describeError } from '../../core/api/problem-details';
import { CodeHighlightDirective } from '../code-highlight/code-highlight.directive';

@Component({
    imports: [CodeHighlightDirective, FormsModule, TuiButton, TuiLoader, TuiNotificationDirective, TuiTextarea, TuiTextfield],
    selector: 'app-bbcode-editor',
    styleUrl: './bbcode-editor.css',
    templateUrl: './bbcode-editor.html',
})
export class BbcodeEditor {
    readonly label = input.required<string>();
    readonly name = input.required<string>();
    readonly value = input.required<string>();
    readonly valueChange = output<string>();
    readonly limit = input<number | null>(null);
    readonly rows = input(8);
    readonly maxRows = input(20);
    readonly placeholder = input('');

    @ViewChild('textarea') private textarea?: ElementRef<HTMLTextAreaElement>;

    private readonly api = inject(BbcodeApi);

    protected readonly mode = signal<'edit' | 'preview'>('edit');
    protected readonly html = signal<string | null>(null);
    protected readonly previewedText = signal<string | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly canPreview = computed(() => '' !== this.value().trim());

    focus(): void {
        this.mode.set('edit');
        setTimeout(() => this.textarea?.nativeElement.focus());
    }

    protected async switchMode(index: number): Promise<void> {
        if (0 === index) {
            this.mode.set('edit');

            return;
        }

        const text = this.value().trim();

        if ('' === text) {
            return;
        }

        this.mode.set('preview');

        if (null !== this.html() && this.previewedText() === text) {
            return;
        }

        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.api.preview(text);

            this.html.set(result.html);
            this.previewedText.set(text);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
