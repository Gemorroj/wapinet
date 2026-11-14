import { HttpErrorResponse } from '@angular/common/http';
import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiCell, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { ArchiveResponse } from '../../api/generated/models';
import { ArchiverApi, ArchiveSource } from '../../core/api/archiver-api';
import { saveBlob } from '../../core/browser/download';
import { formatBytes } from '../../core/format/format-bytes';
import { describeError } from '../../core/api/problem-details';
import { ArchiveEntryView, ArchiveList } from '../../shared/archive-list/archive-list';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { FileSource } from '../../shared/file-source/file-source';

type Mode = 'menu' | 'create' | 'extract' | 'edit';

@Component({
    imports: [
        ArchiveList,
        Breadcrumbs,
        FileSource,
        FormsModule,
        RouterLink,
        TuiButton,
        TuiCardLarge,
        TuiCell,
        TuiIcon,
        TuiLoader,
        TuiNotificationDirective,
        TuiTitle,
    ],
    selector: 'app-archiver',
    styleUrl: './archiver.css',
    templateUrl: './archiver.html',
})
export class Archiver implements OnInit {
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly archiverApi = inject(ArchiverApi);
    private readonly destroyRef = inject(DestroyRef);
    private token: string | null = this.route.snapshot.paramMap.get('archive');

    protected readonly mode = signal<Mode>(this.token ? 'edit' : ((this.route.snapshot.data['mode'] as Mode | undefined) ?? 'menu'));
    protected readonly archive = signal<ArchiveResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly submitting = signal(false);
    protected readonly downloading = signal(false);
    protected readonly busyPath = signal<string | null>(null);
    protected readonly error = signal<string | null>(null);
    protected readonly pendingDelete = signal<string | null>(null);
    protected readonly confirmingArchive = signal(false);
    protected readonly deleting = signal(false);
    protected readonly sourceFiles = signal<File[]>([]);
    protected readonly sourceUrl = signal('');
    protected readonly entries = computed<ArchiveEntryView[]>(() =>
        (this.archive()?.items ?? []).map((item) => ({
            path: item.path,
            size: item.size,
            isDirectory: item.isDirectory,
            isEncrypted: item.isEncrypted,
        })),
    );
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const items: Breadcrumb[] = [
            { label: 'Главная', link: '/' },
            'menu' === this.mode() ? { label: 'Архиватор' } : { label: 'Архиватор', link: '/archiver' },
        ];

        if ('create' === this.mode()) {
            items.push({ label: 'Создать архив' });
        } else if ('extract' === this.mode()) {
            items.push({ label: 'Распаковать архив' });
        } else if ('edit' === this.mode()) {
            items.push({ label: 'Архив' });
        }

        return items;
    });

    ngOnInit(): void {
        this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            const token = params.get('archive');

            if (null === token || this.archive()?.archive === token) {
                return;
            }

            this.token = token;

            if ('edit' !== this.mode()) {
                this.mode.set('edit');
            }

            void this.load(token);
        });
    }

    protected async submit(): Promise<void> {
        const source = this.source();

        if (null === source) {
            return;
        }

        this.submitting.set(true);
        this.error.set(null);

        try {
            const result = 'extract' === this.mode() ? await this.archiverApi.extract(source) : await this.archiverApi.create(source);

            this.archive.set(result);
            await this.router.navigate(['/archiver/edit', result.archive], { replaceUrl: true });
        } catch (error) {
            this.fail(error);
        } finally {
            this.submitting.set(false);
        }
    }

    protected async add(): Promise<void> {
        const archive = this.archive();
        const source = this.source();

        if (!archive || null === source) {
            return;
        }

        this.submitting.set(true);
        this.error.set(null);

        try {
            this.archive.set(await this.archiverApi.add(archive.archive, source));
            this.resetSource();
        } catch (error) {
            this.fail(error);
        } finally {
            this.submitting.set(false);
        }
    }

    protected async confirmDelete(path: string): Promise<void> {
        const archive = this.archive();

        if (!archive) {
            return;
        }

        this.busyPath.set(path);
        this.error.set(null);

        try {
            this.archive.set(await this.archiverApi.remove(archive.archive, path));
            this.pendingDelete.set(null);
        } catch (error) {
            this.fail(error);
        } finally {
            this.busyPath.set(null);
        }
    }

    protected async downloadAll(): Promise<void> {
        const archive = this.archive();

        if (!archive || this.downloading()) {
            return;
        }

        this.downloading.set(true);
        this.error.set(null);

        try {
            saveBlob(await this.archiverApi.download(archive.archive), archive.archive + '.zip');
        } catch (error) {
            this.fail(error);
        } finally {
            this.downloading.set(false);
        }
    }

    protected async downloadEntry(entry: ArchiveEntryView): Promise<void> {
        const archive = this.archive();

        if (!archive) {
            return;
        }

        this.busyPath.set(entry.path);
        this.error.set(null);

        try {
            saveBlob(await this.archiverApi.downloadFile(archive.archive, entry.path), entry.path.split('/').pop() ?? 'file');
        } catch (error) {
            this.fail(error);
        } finally {
            this.busyPath.set(null);
        }
    }

    protected askDelete(path: string): void {
        this.pendingDelete.set(path === this.pendingDelete() ? null : path);
    }

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }

    protected async deleteArchive(): Promise<void> {
        const archive = this.archive();

        if (!archive || this.deleting()) {
            return;
        }

        this.deleting.set(true);
        this.error.set(null);

        try {
            await this.archiverApi.removeArchive(archive.archive);
            await this.router.navigate(['/archiver']);
        } catch (error) {
            this.fail(error);
        } finally {
            this.deleting.set(false);
            this.confirmingArchive.set(false);
        }
    }

    protected resetSource(): void {
        this.sourceFiles.set([]);
        this.sourceUrl.set('');
    }

    private async load(token: string): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            this.archive.set(await this.archiverApi.show(token));
        } catch (error) {
            this.archive.set(null);

            if (error instanceof HttpErrorResponse && 404 === error.status) {
                this.error.set('Архив не найден или срок хранения истёк.');
                this.mode.set('menu');
            } else {
                this.error.set(describeError(error));
            }
        } finally {
            this.loading.set(false);
        }
    }

    private source(): ArchiveSource | null {
        const files = this.sourceFiles();
        const url = this.sourceUrl().trim();

        if (0 === files.length && '' === url) {
            this.error.set('Выберите файл или укажите ссылку.');

            return null;
        }

        if ('extract' === this.mode() && (1 !== files.length || '' !== url)) {
            this.error.set('Для распаковки выберите один архив.');

            return null;
        }

        return { files, url: '' === url ? null : url };
    }

    private fail(error: unknown): void {
        if (error instanceof HttpErrorResponse && 404 === error.status && this.token) {
            // The archive may have expired or an entry was removed in another tab.
            // Reloading shows the current state instead of a dead list.
            this.pendingDelete.set(null);
            this.confirmingArchive.set(false);
            void this.load(this.token);

            return;
        }

        this.error.set(describeError(error));
    }
}
