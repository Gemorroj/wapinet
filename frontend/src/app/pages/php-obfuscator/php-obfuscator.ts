import { Component, DestroyRef, OnInit, ViewChild, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Data, Router } from '@angular/router';
import { TuiButton, TuiCheckbox, TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';
import { TuiCardLarge } from '@taiga-ui/layout';

import { ObfuscatorInfoResponse, ObfuscatorLevel } from '../../api/generated/models';
import { formatBytes } from '../../core/format/format-bytes';
import { ObfuscatorApi } from '../../core/api/obfuscator-api';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { CodeBlock } from '../../shared/code-block/code-block';
import { CodeSourcePanel } from '../../shared/code-source/code-source';
import { HelpSection } from '../../shared/help-section/help-section';
import { ToolResult } from '../../shared/tool-result/tool-result';

interface ObfuscatorTab {
    readonly id: 'tool' | 'about';
    readonly label: string;
    readonly title: string;
    readonly path: string;
}

const OBFUSCATOR_TABS: readonly ObfuscatorTab[] = [
    { id: 'tool', label: 'Обфускатор', title: 'PHP-обфускатор', path: '/php_obfuscator' },
    { id: 'about', label: 'Справка', title: 'Об обфускации', path: '/php_obfuscator/about' },
];

interface ObfuscatorPreset {
    readonly value: ObfuscatorLevel;
    readonly label: string;
    readonly hint: string;
}

const OBFUSCATOR_LEVELS: readonly ObfuscatorPreset[] = [
    {
        value: 'light',
        label: 'Лёгкая',
        hint: 'Кодируются числа; комментарии удаляются по желанию.',
    },
    {
        value: 'normal',
        label: 'Обычная',
        hint: 'Кодируются строки и числа; комментарии удаляются по желанию.',
    },
    {
        value: 'max',
        label: 'Максимальная',
        hint: 'Переименовываются переменные, кодируются вызовы, строки и числа.',
    },
];

@Component({
    imports: [
        Breadcrumbs,
        CodeBlock,
        CodeSourcePanel,
        FormsModule,
        HelpSection,
        ToolResult,
        TuiButton,
        TuiCardLarge,
        TuiCheckbox,
        TuiChip,
        TuiLoader,
        TuiNotificationDirective,
        TuiTabs,
    ],
    selector: 'app-php-obfuscator',
    styleUrl: './php-obfuscator.css',
    templateUrl: './php-obfuscator.html',
})
export class PhpObfuscator implements OnInit {
    private readonly api = inject(ObfuscatorApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    @ViewChild(CodeSourcePanel) private sourcePanel?: CodeSourcePanel;

    protected readonly tabs = OBFUSCATOR_TABS;
    protected readonly levels = OBFUSCATOR_LEVELS;
    protected readonly tabIndex = signal(0);
    protected readonly code = signal('');
    protected readonly removeComments = signal(true);
    protected readonly level = signal<ObfuscatorLevel>('max');
    protected readonly result = signal('');
    protected readonly sizes = signal<{ source: number; result: number } | null>(null);
    protected readonly error = signal<string | null>(null);
    protected readonly loading = signal(false);
    protected readonly info = signal<ObfuscatorInfoResponse | null>(null);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const tab = OBFUSCATOR_TABS[this.tabIndex()] ?? OBFUSCATOR_TABS[0];

        return 0 === this.tabIndex()
            ? [{ label: 'Главная', link: '/' }, { label: 'PHP-обфускатор' }]
            : [{ label: 'Главная', link: '/' }, { label: 'PHP-обфускатор', link: '/php_obfuscator' }, { label: tab.title }];
    });
    protected readonly maxCodeSizeKb = computed(() => Math.round((this.info()?.maxCodeSize ?? 0) / 1024));
    protected readonly phpVersion = computed(() => this.info()?.phpVersion.split('.').slice(0, 2).join('.') ?? '');
    protected readonly sizeInfo = computed(() => {
        const sizes = this.sizes();

        if (null === sizes || sizes.source <= 0 || !Number.isFinite(sizes.source) || !Number.isFinite(sizes.result)) {
            return '';
        }

        const percent = Math.round((sizes.result / sizes.source - 1) * 100);

        return `${formatBytes(sizes.source)} → ${formatBytes(sizes.result)} (${percent >= 0 ? '+' : ''}${percent}%)`;
    });
    protected readonly levelHint = computed(() => OBFUSCATOR_LEVELS.find((item) => item.value === this.level())?.hint ?? '');

    ngOnInit(): void {
        this.route.data.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((data) => {
            this.tabIndex.set(PhpObfuscator.tabIndex(data));
        });

        void this.loadInfo();
    }

    protected selectTab(index: number): void {
        const tab = OBFUSCATOR_TABS[index];

        if (!tab || index === this.tabIndex()) {
            return;
        }

        this.tabIndex.set(index);
        void this.router.navigateByUrl(tab.path);
    }

    protected async obfuscate(): Promise<void> {
        const source = await this.sourcePanel?.resolve();

        if (!source) {
            return;
        }

        this.loading.set(true);
        this.error.set(null);
        this.result.set('');
        this.sizes.set(null);

        try {
            const response = await this.api.obfuscate(source, this.removeComments(), this.level());

            this.result.set(response.code);
            this.sizes.set({ source: response.sourceSize, result: response.resultSize });
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected setCode(code: string): void {
        this.code.set(code);
        this.onSourceChanged();
    }

    protected setLevel(level: ObfuscatorLevel): void {
        this.level.set(level);
        this.onSourceChanged();
    }

    protected onSourceChanged(): void {
        this.result.set('');
        this.sizes.set(null);
        this.error.set(null);
    }

    private async loadInfo(): Promise<void> {
        try {
            this.info.set(await this.api.info());
        } catch {
            // Справка остаётся читаемой, если API недоступен.
        }
    }

    private static tabIndex(data: Data): number {
        const index = OBFUSCATOR_TABS.findIndex((tab) => tab.id === data['tab']);

        return index < 0 ? 0 : index;
    }
}
