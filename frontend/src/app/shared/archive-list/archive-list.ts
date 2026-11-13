import { ChangeDetectionStrategy, Component, computed, input, output } from '@angular/core';
import { TuiButton, TuiIcon, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';

import { fileIcon } from '../../core/file-icon';
import { formatBytes } from '../../core/format-bytes';

export interface ArchiveEntryView {
    path: string;
    size: number;
    isDirectory: boolean;
    isEncrypted: boolean;
}

interface ArchiveRow {
    entry: ArchiveEntryView;
    name: string;
    depth: number;
}

/**
 * Tree-like archive contents, shared by the file card and the archiver.
 */
@Component({
    imports: [TuiBadge, TuiButton, TuiIcon, TuiNotificationDirective],
    selector: 'app-archive-list',
    styleUrl: './archive-list.css',
    templateUrl: './archive-list.html',
    changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ArchiveList {
    readonly entries = input.required<readonly ArchiveEntryView[]>();
    readonly busyPath = input<string | null>(null);
    readonly showAction = input(true);
    readonly showDelete = input(false);
    readonly actionLabel = input('Скачать');
    readonly actionIcon = input('@tui.download');
    readonly emptyText = input('Архив пуст.');
    readonly entryAction = output<ArchiveEntryView>();
    readonly deleteEntry = output<ArchiveEntryView>();

    protected readonly rows = computed<ArchiveRow[]>(() => {
        const byPath = new Map<string, ArchiveEntryView>();

        for (const entry of this.entries()) {
            const segments = entry.path.split('/').filter(Boolean);

            if (entry.isDirectory) {
                byPath.set(segments.join('/'), entry);
                continue;
            }

            for (let index = 1; index < segments.length; index++) {
                const directory = segments.slice(0, index).join('/');

                if (!byPath.has(directory)) {
                    byPath.set(directory, {
                        path: directory + '/',
                        size: 0,
                        isDirectory: true,
                        isEncrypted: false,
                    });
                }
            }

            byPath.set(segments.join('/'), entry);
        }

        return [...byPath.entries()]
            .sort(([left], [right]) => left.localeCompare(right))
            .map(([path, entry]) => {
                const segments = path.split('/').filter(Boolean);

                return {
                    entry,
                    name: segments[segments.length - 1] ?? path,
                    depth: Math.max(0, segments.length - 1),
                };
            });
    });

    protected readonly summary = computed(() => {
        const entries = this.entries();
        const files = entries.filter((entry) => !entry.isDirectory);

        return {
            files: files.length,
            folders: entries.length - files.length,
            size: files.reduce((total, entry) => total + entry.size, 0),
        };
    });

    protected icon(row: ArchiveRow): string {
        return row.entry.isDirectory ? '@tui.folder' : fileIcon('', '', row.name);
    }

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }

    protected plural(count: number, one: string, few: string, many: string): string {
        const mod10 = count % 10;
        const mod100 = count % 100;

        if (1 === mod10 && 11 !== mod100) {
            return one;
        }

        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
            return few;
        }

        return many;
    }

    protected action(entry: ArchiveEntryView): void {
        if (!entry.isDirectory) {
            this.entryAction.emit(entry);
        }
    }

    protected remove(entry: ArchiveEntryView): void {
        this.deleteEntry.emit(entry);
    }
}
