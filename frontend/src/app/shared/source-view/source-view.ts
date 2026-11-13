import { Component, computed, input } from '@angular/core';

@Component({
    selector: 'app-source-view',
    styleUrl: './source-view.css',
    templateUrl: './source-view.html',
})
export class SourceView {
    readonly code = input.required<string>();
    readonly errorLines = input<readonly number[]>([]);
    readonly warningLines = input<readonly number[]>([]);
    readonly idPrefix = input('source-line');

    protected readonly lines = computed(() => this.code().split('\n'));

    protected lineId(line: number): string {
        return `${this.idPrefix()}-${line}`;
    }

    protected isError(line: number): boolean {
        return this.errorLines().includes(line);
    }

    protected isWarning(line: number): boolean {
        return !this.isError(line) && this.warningLines().includes(line);
    }
}
