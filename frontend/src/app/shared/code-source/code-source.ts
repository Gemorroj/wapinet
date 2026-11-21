import { Component, input, output, signal } from '@angular/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';

import { CodeSource } from '../../core/api/code-source';
import { CodeField } from '../code-field/code-field';
import { FileSource } from '../file-source/file-source';

@Component({
    imports: [CodeField, FileSource, TuiChip],
    selector: 'app-code-source',
    styleUrl: './code-source.css',
    templateUrl: './code-source.html',
})
export class CodeSourcePanel {
    readonly label = input.required<string>();
    readonly placeholder = input('');
    readonly accept = input('');
    readonly rows = input(10);
    readonly code = input.required<string>();
    readonly loading = input(false);
    readonly codeChange = output<string>();
    readonly changed = output<void>();
    readonly error = output<string>();

    protected readonly mode = signal<'code' | 'file'>('code');
    protected readonly files = signal<File[]>([]);
    protected readonly url = signal('');

    reset(): void {
        this.mode.set('code');
        this.files.set([]);
        this.url.set('');
    }

    async resolve(): Promise<CodeSource | null> {
        if ('code' === this.mode()) {
            if ('' === this.code().trim()) {
                this.error.emit('Вставьте код.');
                return null;
            }

            return { code: this.code() };
        }

        const url = this.url().trim();

        if ('' !== url) {
            return { url };
        }

        const file = this.files()[0];

        if (!file) {
            this.error.emit('Выберите файл или укажите ссылку.');
            return null;
        }

        try {
            return { code: await file.text() };
        } catch {
            this.error.emit('Не удалось прочитать файл.');
            return null;
        }
    }

    protected setCode(code: string): void {
        this.codeChange.emit(code);
        this.changed.emit();
    }

    protected useCode(): void {
        this.mode.set('code');
        this.changed.emit();
    }

    protected useFile(): void {
        this.mode.set('file');
        this.changed.emit();
    }

    protected setFiles(files: File[]): void {
        this.files.set(files);
        this.changed.emit();
    }

    protected setUrl(url: string): void {
        this.url.set(url);
        this.changed.emit();
    }
}
