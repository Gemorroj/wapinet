import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Data, Router } from '@angular/router';
import { TuiButton, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';
import { TuiCardLarge } from '@taiga-ui/layout';

import { copyText } from '../../core/browser/clipboard';
import { HashApi } from '../../core/api/hash-api';
import { DEFAULT_HASH_SELECTION, HASH_ALGORITHMS, HashAlgorithmId, HashError, HashResult, hashFile, hashText } from './hash-codec';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { CodeField } from '../../shared/code-field/code-field';
import { FileSource } from '../../shared/file-source/file-source';
import { HelpSection } from '../../shared/help-section/help-section';

type HashMode = 'text' | 'file';
type HashSource = { readonly text: string } | { readonly file: File };

interface HashTab {
    readonly id: string;
    readonly label: string;
    readonly title: string;
    readonly path: string;
}

const HASH_TABS: readonly HashTab[] = [
    { id: 'tool', label: 'Хэши', title: 'Хэши', path: '/hash' },
    { id: 'about', label: 'Справка', title: 'О хэшах', path: '/hash/about' },
];

@Component({
    imports: [
        Breadcrumbs,
        CodeField,
        FileSource,
        HelpSection,
        TuiButton,
        TuiCardLarge,
        TuiChip,
        TuiIcon,
        TuiLoader,
        TuiNotificationDirective,
        TuiTabs,
        TuiTitle,
    ],
    selector: 'app-hash',
    styleUrl: './hash.css',
    templateUrl: './hash.html',
})
export class HashTool implements OnInit {
    private readonly api = inject(HashApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);
    private runId = 0;
    private copiedTimer: ReturnType<typeof setTimeout> | null = null;

    protected readonly tabs = HASH_TABS;
    protected readonly tabIndex = signal(0);
    protected readonly breadcrumbs = signal<Breadcrumb[]>([{ label: 'Главная', link: '/' }, { label: 'Хэши' }]);
    protected readonly mode = signal<HashMode>('text');
    protected readonly text = signal('');
    protected readonly files = signal<File[]>([]);
    protected readonly url = signal('');
    protected readonly selected = signal<readonly HashAlgorithmId[]>(DEFAULT_HASH_SELECTION);
    protected readonly results = signal<HashResult[]>([]);
    protected readonly error = signal<string | null>(null);
    protected readonly loading = signal(false);
    protected readonly copied = signal<HashAlgorithmId | null>(null);
    protected readonly algorithms = HASH_ALGORITHMS;

    constructor() {
        this.destroyRef.onDestroy(() => {
            if (this.copiedTimer) {
                clearTimeout(this.copiedTimer);
            }
        });
    }

    ngOnInit(): void {
        this.route.data.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((data) => {
            const index = HashTool.tabIndex(data);

            this.tabIndex.set(index);
            this.breadcrumbs.set(HashTool.breadcrumbs(index));
        });
    }

    protected selectTab(index: number): void {
        const tab = HASH_TABS[index];

        if (!tab) {
            return;
        }

        void this.router.navigateByUrl(tab.path);
    }

    protected useText(): void {
        this.mode.set('text');
        this.files.set([]);
        this.url.set('');
        void this.evaluate('' === this.text() ? null : { text: this.text() });
    }

    protected useFile(): void {
        this.mode.set('file');
        this.text.set('');
        this.results.set([]);
        this.error.set(null);
    }

    protected async setText(value: string): Promise<void> {
        this.text.set(value);
        await this.evaluate('' === value ? null : { text: value });
    }

    protected async setFiles(files: File[]): Promise<void> {
        const file = files[0];

        this.files.set(files);
        this.url.set('');
        await this.evaluate(file ? { file } : null);
    }

    protected setUrl(url: string): void {
        this.url.set(url);
        this.results.set([]);
        this.error.set(null);
    }

    protected toggleAlgorithm(id: HashAlgorithmId): void {
        const selected = this.selected();
        const next = selected.includes(id) ? selected.filter((item) => item !== id) : [...selected, id];

        if (!next.length) {
            return;
        }

        this.selected.set(next);

        if ('file' === this.mode() && this.files()[0]) {
            void this.evaluate({ file: this.files()[0] });
        } else if ('text' === this.mode()) {
            void this.evaluate('' === this.text() ? null : { text: this.text() });
        } else {
            this.results.set([]);
        }
    }

    protected isSelected(id: HashAlgorithmId): boolean {
        return this.selected().includes(id);
    }

    protected async hashUrl(): Promise<void> {
        const url = this.url().trim();

        if ('' === url) {
            return;
        }

        const runId = ++this.runId;

        this.loading.set(true);
        this.error.set(null);

        try {
            const response = await this.api.hashUrl(url, this.selected());

            if (runId === this.runId) {
                this.results.set(
                    HASH_ALGORITHMS.filter((item) => this.selected().includes(item.id)).map((item) => ({
                        id: item.id,
                        label: item.label,
                        value: response.hashes[item.id] ?? '',
                    })),
                );
            }
        } catch (error) {
            if (runId === this.runId) {
                this.results.set([]);
                this.error.set(describeError(error));
            }
        } finally {
            if (runId === this.runId) {
                this.loading.set(false);
            }
        }
    }

    protected async copy(item: HashResult): Promise<void> {
        await copyText(item.value);

        this.copied.set(item.id);

        if (this.copiedTimer) {
            clearTimeout(this.copiedTimer);
        }

        this.copiedTimer = setTimeout(() => this.copied.set(null), 1500);
    }

    private async evaluate(source: HashSource | null): Promise<void> {
        const runId = ++this.runId;

        this.error.set(null);

        if (null === source) {
            this.results.set([]);
            this.loading.set(false);

            return;
        }

        this.loading.set(true);

        try {
            const results = 'text' in source ? hashText(source.text, this.selected()) : await hashFile(source.file, this.selected());

            if (runId === this.runId) {
                this.results.set(results);
            }
        } catch (error) {
            if (runId === this.runId) {
                this.results.set([]);
                this.error.set(error instanceof HashError ? error.message : 'Не удалось посчитать хэш.');
            }
        } finally {
            if (runId === this.runId) {
                this.loading.set(false);
            }
        }
    }

    private static tabIndex(data: Data): number {
        const index = HASH_TABS.findIndex((tab) => tab.id === data['tab']);

        return index < 0 ? 0 : index;
    }

    private static breadcrumbs(index: number): Breadcrumb[] {
        const tab = HASH_TABS[index] ?? HASH_TABS[0];

        if (0 === index) {
            return [{ label: 'Главная', link: '/' }, { label: tab.title }];
        }

        return [{ label: 'Главная', link: '/' }, { label: 'Хэши', link: '/hash' }, { label: tab.title }];
    }
}
