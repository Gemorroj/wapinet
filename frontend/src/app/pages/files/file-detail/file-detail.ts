import { HttpErrorResponse } from '@angular/common/http';
import { Component, DestroyRef, OnDestroy, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiCell, TuiIcon, TuiInput, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiCardLarge } from '@taiga-ui/layout';

import { ArchiveEntryResponse, FileResponse } from '../../../api/generated/models';
import { Auth } from '../../../core/auth';
import { copyText } from '../../../core/clipboard';
import { saveBlob } from '../../../core/download';
import { fileIcon } from '../../../core/file-icon';
import { fileCategoryLabel } from '../../../core/file-category';
import { FilesApi } from '../../../core/files-api';
import { formatBytes } from '../../../core/format-bytes';
import { formatDuration } from '../../../core/format-duration';
import { describeError } from '../../../core/problem-details';
import { ArchiveEntryView, ArchiveList } from '../../../shared/archive-list/archive-list';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { CodeHighlightDirective } from '../../../shared/code-highlight/code-highlight.directive';
import { InfoHint } from '../../../shared/info-hint/info-hint';
import { RelativeDatePipe } from '../../../shared/relative-date/relative-date.pipe';
import { VkComments } from '../../../shared/vk-comments/vk-comments';
import { FilePermissions } from './file-permissions/file-permissions';

const META_LABELS: Record<string, string> = {
    codecName: 'Кодек',
    bitRate: 'Битрейт',
    audioCodecName: 'Аудиокодек',
    audioBitRate: 'Аудиобитрейт',
    audioSampleRate: 'Частота дискретизации',
    sampleRate: 'Частота дискретизации',
    dateTimeOriginal: 'Дата съёмки',
    dateTime: 'Дата изменения',
    make: 'Производитель',
    model: 'Модель',
    software: 'Редактировалось в',
    comment: 'Комментарий',
    versionName: 'Версия',
    packageName: 'Пакет',
    minSdkVersions: 'Минимальная версия Android',
    permissions: 'Права доступа',
    size: 'Размер данных',
    name: 'Имя',
    datetime: 'Дата создания',
};

const CONVERSION_POLL_INTERVAL = 5000;
const CONVERSION_POLL_ATTEMPTS = 60;

@Component({
    imports: [
        ArchiveList,
        Breadcrumbs,
        CodeHighlightDirective,
        FilePermissions,
        InfoHint,
        RelativeDatePipe,
        FormsModule,
        RouterLink,
        TuiBadge,
        TuiButton,
        TuiCardLarge,
        TuiCell,
        TuiChip,
        TuiIcon,
        TuiInput,
        TuiLoader,
        TuiNotificationDirective,
        TuiTitle,
        VkComments,
    ],
    selector: 'app-file-detail',
    styleUrl: './file-detail.css',
    templateUrl: './file-detail.html',
})
export class FileDetail implements OnInit, OnDestroy {
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly filesApi = inject(FilesApi);
    private readonly destroyRef = inject(DestroyRef);
    protected readonly auth = inject(Auth);

    private conversionTimer: ReturnType<typeof setInterval> | null = null;

    protected readonly file = signal<FileResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly passwordRequired = signal(false);
    protected readonly password = signal('');
    protected readonly unlocking = signal(false);
    protected readonly downloading = signal(false);
    protected readonly archiveEntries = signal<ArchiveEntryResponse[]>([]);
    protected readonly archiveLoading = signal(false);
    protected readonly archiveError = signal<string | null>(null);
    protected readonly archivePasswordRequired = signal(false);
    protected readonly archivePassword = signal('');
    protected readonly archiveDownloading = signal<string | null>(null);
    protected readonly copyStatus = signal<'success' | 'error' | null>(null);
    protected readonly pendingDelete = signal(false);
    protected readonly deleting = signal(false);
    protected readonly publishing = signal(false);
    private copyTimer: ReturnType<typeof setTimeout> | null = null;

    /** Polling stopped before the conversion finished, so the page no longer refreshes itself. */
    protected readonly conversionPollingStalled = signal(false);

    protected readonly isAdmin = this.auth.isAdmin;
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Файлообменник', link: '/files' },
        { label: this.file()?.name ?? 'Файл' },
    ]);
    protected readonly metaEntries = computed(() => {
        const meta = this.file()?.meta;

        if (!meta || Array.isArray(meta)) {
            return [];
        }

        const entries: Array<{ label: string; value: string }> = [];
        const width = this.metaNumber(meta, 'width');
        const height = this.metaNumber(meta, 'height');

        if (null !== width && null !== height) {
            entries.push({ label: 'Разрешение', value: `${width}×${height}` });
        }

        for (const [key, value] of Object.entries(meta)) {
            if ('width' === key || 'height' === key) {
                continue;
            }

            if ('duration' === key) {
                entries.push({ label: 'Длительность', value: formatDuration(Number(value)) });
                continue;
            }

            if ('bitRate' === key || 'audioBitRate' === key) {
                entries.push({ label: META_LABELS[key], value: `${Math.round(Number(value) / 1000)} кбит/с` });
                continue;
            }

            if ('sampleRate' === key || 'audioSampleRate' === key) {
                entries.push({ label: META_LABELS[key], value: `${Math.round(Number(value) / 1000)} кГц` });
                continue;
            }

            if ('permissions' === key) {
                continue;
            }

            if ('minSdkVersions' === key && Array.isArray(value)) {
                entries.push({ label: META_LABELS[key], value: value.join(', ') });
                continue;
            }

            if ('size' === key && 'number' === typeof value) {
                entries.push({ label: META_LABELS[key], value: formatBytes(value) });
                continue;
            }

            entries.push({ label: META_LABELS[key] ?? key, value: this.formatMeta(value) });
        }

        return entries;
    });

    private fileId = 0;

    async ngOnInit(): Promise<void> {
        // The component is reused when moving between files, so the id has to be watched.
        this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            void this.open(Number(params.get('id')));
        });
    }

    ngOnDestroy(): void {
        this.stopConversionPolling();

        if (null !== this.copyTimer) {
            clearTimeout(this.copyTimer);
        }
    }

    private async open(id: number): Promise<void> {
        this.stopConversionPolling();
        this.resetState();
        this.fileId = id;

        if (!Number.isInteger(id) || id <= 0) {
            this.error.set('Файл не найден.');

            return;
        }

        await this.load();
    }

    private resetState(): void {
        this.file.set(null);
        this.error.set(null);
        this.passwordRequired.set(false);
        this.password.set('');
        this.archiveEntries.set([]);
        this.archiveLoading.set(false);
        this.archiveError.set(null);
        this.archivePasswordRequired.set(false);
        this.archivePassword.set('');
        this.archiveDownloading.set(null);
        this.pendingDelete.set(false);
        this.deleting.set(false);
        this.publishing.set(false);
        this.conversionPollingStalled.set(false);
    }

    protected async unlock(): Promise<void> {
        if (this.password() === '') {
            this.error.set('Введите пароль.');
            return;
        }

        this.unlocking.set(true);
        this.error.set(null);

        try {
            const file = await this.filesApi.show(this.fileId, this.password());
            this.file.set(file);
            this.passwordRequired.set(false);
            this.scheduleConversionPolling();
        } catch (error) {
            this.error.set(error instanceof HttpErrorResponse && 403 === error.status ? 'Неверный пароль.' : describeError(error));
        } finally {
            this.unlocking.set(false);
        }
    }

    protected async download(): Promise<void> {
        this.downloading.set(true);
        this.error.set(null);

        try {
            saveBlob(await this.filesApi.download(this.fileId, this.password() || undefined), this.file()?.name ?? 'file');
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.downloading.set(false);
        }
    }

    protected async unlockArchive(): Promise<void> {
        if (this.archivePassword() === '') {
            this.archiveError.set('Введите пароль архива.');
            return;
        }

        await this.loadArchive();
    }

    protected async downloadArchiveEntry(entry: ArchiveEntryView): Promise<void> {
        if (entry.isDirectory) {
            return;
        }

        this.archiveDownloading.set(entry.path);
        this.archiveError.set(null);

        try {
            const blob = await this.filesApi.archiveEntry(this.fileId, entry.path, this.archivePassword() || undefined);

            saveBlob(blob, entry.path.split('/').pop() ?? 'entry');
        } catch (error) {
            if (error instanceof HttpErrorResponse && error.status === 403) {
                this.archivePasswordRequired.set(true);

                if (this.archivePassword() !== '') {
                    this.archiveError.set('Неверный пароль архива.');
                }
            } else {
                this.archiveError.set(describeError(error));
            }
        } finally {
            this.archiveDownloading.set(null);
        }
    }

    protected async edit(): Promise<void> {
        await this.router.navigate(['/files', this.fileId, 'edit']);
    }

    protected async publish(): Promise<void> {
        this.publishing.set(true);
        this.error.set(null);

        try {
            const file = await this.filesApi.update(this.fileId, { hidden: false });
            this.file.set(file);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.publishing.set(false);
        }
    }

    protected async confirmDelete(): Promise<void> {
        this.deleting.set(true);
        this.error.set(null);

        try {
            await this.filesApi.delete(this.fileId);
            await this.router.navigate(['/files']);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.deleting.set(false);
            this.pendingDelete.set(false);
        }
    }

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }

    protected shortHash(hash: string): string {
        return hash.slice(0, 7);
    }

    protected async copyHash(hash: string): Promise<void> {
        const copied = await copyText(hash);

        this.copyStatus.set(copied ? 'success' : 'error');

        if (null !== this.copyTimer) {
            clearTimeout(this.copyTimer);
        }

        this.copyTimer = setTimeout(() => this.copyStatus.set(null), 2000);
    }

    protected typeIcon(file: FileResponse): string {
        return fileIcon(file.category, file.mimeType, file.name);
    }

    protected categoryLabel(category: string): string {
        return fileCategoryLabel(category);
    }

    protected formatMeta(value: unknown): string {
        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'object') {
            return JSON.stringify(value);
        }

        return String(value);
    }

    private metaNumber(meta: { [key: string]: unknown }, key: string): number | null {
        const value = meta[key];

        return typeof value === 'number' ? value : null;
    }

    protected isImage(file: FileResponse): boolean {
        return file.mimeType.startsWith('image/');
    }

    /**
     * Images that the browser can render itself, even without a generated thumbnail.
     */
    protected isVectorImage(file: FileResponse): boolean {
        return ['image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'].includes(file.mimeType);
    }

    protected hasPreview(file: FileResponse): boolean {
        return (
            (this.isImage(file) && (null !== file.links.thumbnailUrl || this.isVectorImage(file))) ||
            (file.category === 'video' && (file.content.playableVideo || null !== file.links.thumbnailUrl)) ||
            (file.category === 'audio' && (file.content.playableAudio || null !== file.links.thumbnailUrl)) ||
            ((file.category === 'android' || file.category === 'java') && null !== file.links.thumbnailUrl)
        );
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const file = await this.filesApi.show(this.fileId);
            this.file.set(file);
            this.scheduleConversionPolling();

            if (file.category === 'archive') {
                await this.loadArchive();
            }
        } catch (error) {
            if (error instanceof HttpErrorResponse && error.status === 403) {
                this.passwordRequired.set(true);
            } else {
                this.error.set(describeError(error));
            }
        } finally {
            this.loading.set(false);
        }
    }

    /**
     * Conversion runs in a worker, so the page polls the API until the converted file appears
     * (or gives up after a few minutes to avoid an endless request loop).
     */
    private scheduleConversionPolling(): void {
        this.stopConversionPolling();
        this.conversionPollingStalled.set(false);

        if (!this.file()?.content.conversionPending) {
            return;
        }

        let attempts = 0;

        this.conversionTimer = setInterval(() => {
            // The tab is in the background: no need to spend requests, the next tick will refresh.
            if (document.hidden) {
                return;
            }

            if (++attempts > CONVERSION_POLL_ATTEMPTS) {
                this.stopConversionPolling();
                this.conversionPollingStalled.set(true);

                return;
            }

            void this.refreshConversion();
        }, CONVERSION_POLL_INTERVAL);
    }

    private stopConversionPolling(): void {
        if (null !== this.conversionTimer) {
            clearInterval(this.conversionTimer);
            this.conversionTimer = null;
        }
    }

    private async refreshConversion(): Promise<void> {
        try {
            const file = await this.filesApi.show(this.fileId, this.password() || undefined);

            this.file.set(file);

            if (!file.content.conversionPending) {
                this.stopConversionPolling();
            }
        } catch {
            // A transient error: the next tick retries.
        }
    }

    private async loadArchive(): Promise<void> {
        this.archiveLoading.set(true);
        this.archiveError.set(null);
        try {
            const data = await this.filesApi.archive(this.fileId, this.archivePassword() || undefined);

            this.archiveEntries.set(data.items);
            this.archivePasswordRequired.set(false);
        } catch (error) {
            if (error instanceof HttpErrorResponse && error.status === 403) {
                this.archivePasswordRequired.set(true);

                if (this.archivePassword() !== '') {
                    this.archiveError.set('Неверный пароль архива.');
                }
            } else {
                this.archiveError.set(describeError(error));
            }
        } finally {
            this.archiveLoading.set(false);
        }
    }
}
