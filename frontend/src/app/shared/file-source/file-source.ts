import { Component, EventEmitter, Input, Output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TuiAppearance, TuiInput, TuiLoader } from '@taiga-ui/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiInputFiles } from '@taiga-ui/kit/components/files';

import { formatBytes } from '../../core/format-bytes';

/**
 * Single source of truth for file selection: one `files` array for both modes.
 * The `multiple` flag only toggles the HTML attribute; consumers that need one
 * file take `files[0]`.
 */
@Component({
    imports: [FormsModule, TuiAppearance, TuiChip, TuiInput, TuiInputFiles, TuiLoader],
    selector: 'app-file-source',
    styleUrl: './file-source.css',
    templateUrl: './file-source.html',
})
export class FileSource {
    @Input() accept = '';
    @Input() multiple = false;
    @Input() loading = false;
    @Input() files: File[] = [];
    @Input() url = '';
    @Output() filesChange = new EventEmitter<File[]>();
    @Output() urlChange = new EventEmitter<string>();

    protected readonly mode = signal<'file' | 'url'>('file');
    protected readonly dragging = signal(false);
    private dragDepth = 0;

    protected useFile(): void {
        this.mode.set('file');
        this.setUrl('');
    }

    protected useUrl(): void {
        this.mode.set('url');
        this.filesChange.emit([]);
    }

    protected selectFiles(event: Event): void {
        const input = event.target as HTMLInputElement;
        const files = Array.from(input.files ?? []);

        // Reset so choosing the same files again still fires a change event.
        input.value = '';

        this.filesChange.emit(files);
    }

    protected dragEnter(event: DragEvent): void {
        if (this.loading || !this.hasFiles(event)) {
            return;
        }

        event.preventDefault();
        this.dragDepth++;
        this.dragging.set(true);
    }

    protected dragOver(event: DragEvent): void {
        if (this.loading || !this.hasFiles(event)) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'copy';
        }
    }

    protected dragLeave(event: DragEvent): void {
        if (!this.hasFiles(event)) {
            return;
        }

        event.preventDefault();
        this.dragDepth = Math.max(0, this.dragDepth - 1);

        if (0 === this.dragDepth) {
            this.dragging.set(false);
        }
    }

    protected drop(event: DragEvent): void {
        event.preventDefault();

        this.dragDepth = 0;
        this.dragging.set(false);

        if (this.loading) {
            return;
        }

        const files = Array.from(event.dataTransfer?.files ?? []);

        if (!files.length) {
            return;
        }

        this.mode.set('file');
        this.setUrl('');
        this.filesChange.emit(this.multiple ? files : files.slice(0, 1));
    }

    protected setUrl(value: string): void {
        this.url = value;
        this.urlChange.emit(value);
    }

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }

    private hasFiles(event: DragEvent): boolean {
        return Array.from(event.dataTransfer?.types ?? []).includes('Files');
    }
}
