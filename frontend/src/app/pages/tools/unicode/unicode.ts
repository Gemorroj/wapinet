import { Component, OnDestroy, computed, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiCheckbox, TuiDropdown, TuiInput, TuiTextfield, TuiTitle } from '@taiga-ui/core';
import { TuiDataListWrapper } from '@taiga-ui/kit/components/data-list-wrapper';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiSelect } from '@taiga-ui/kit/components/select';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';
import { TuiChevron } from '@taiga-ui/kit/directives/chevron';
import { TuiCardLarge } from '@taiga-ui/layout';
import { map } from 'rxjs';

import { copyText } from '../../../core/clipboard';
import {
    UnicodeDecodeFormat,
    UnicodeEncodeFormat,
    UnicodeEncodeOptions,
    codePointsLabel,
    decodeUnicode,
    encodeUnicode,
    graphemes,
    inspectGrapheme,
    textStats,
} from '../../../core/unicode';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { CodeBlock } from '../../../shared/code-block/code-block';
import { CodeField } from '../../../shared/code-field/code-field';
import { HelpSection } from '../../../shared/help-section/help-section';
import { ToolResult } from '../../../shared/tool-result/tool-result';
import { UNICODE_SECTIONS, UnicodeSection, UnicodeSymbol } from './unicode-symbols';

type Tab = 'convert' | 'icons' | 'inspector' | 'about';
type ConvertMode = 'encode' | 'decode';

interface TabConfig {
    readonly id: Tab;
    readonly label: string;
    readonly title: string;
    readonly path: string;
}

const TABS: readonly TabConfig[] = [
    { id: 'convert', label: 'Текст', title: 'Конвертер', path: '/unicode' },
    { id: 'icons', label: 'Символы', title: 'Справочник', path: '/unicode/icons' },
    { id: 'inspector', label: 'Инспектор', title: 'Инспектор', path: '/unicode/inspector' },
    { id: 'about', label: 'Справка', title: 'О Unicode', path: '/unicode/about' },
];

const INVISIBLE = /^[\s\u00A0\u00AD\u200B-\u200F\u2028\u2029\u202F\u2060\uFEFF]$/u;

@Component({
    imports: [
        Breadcrumbs,
        CodeBlock,
        CodeField,
        FormsModule,
        HelpSection,
        RouterLink,
        TuiCardLarge,
        TuiCheckbox,
        TuiChip,
        TuiDataListWrapper,
        TuiDropdown,
        TuiInput,
        TuiSelect,
        TuiTabs,
        TuiTextfield,
        TuiTitle,
        TuiChevron,
        ToolResult,
    ],
    selector: 'app-unicode',
    styleUrl: './unicode.css',
    templateUrl: './unicode.html',
})
export class UnicodeTool implements OnDestroy {
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);

    protected readonly tabs = TABS.map((tab) => tab.label);
    protected readonly tabIndex = toSignal(this.route.data.pipe(map((data) => UnicodeTool.tabIndex(data))), {
        initialValue: UnicodeTool.tabIndex(this.route.snapshot.data),
    });
    protected readonly pageTitle = computed(() => TABS[this.tabIndex()]?.title ?? TABS[0].title);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Unicode', link: 0 === this.tabIndex() ? undefined : '/unicode' },
        { label: this.pageTitle() },
    ]);

    protected readonly mode = signal<ConvertMode>('encode');
    protected readonly text = signal('');
    protected readonly latin = signal(false);
    protected readonly trimLeadingZeros = signal(false);
    protected readonly escapeHtml = signal(false);
    protected readonly encodeFormat = signal<string>('HTML');
    protected readonly decodeFormat = signal<string>('Авто');
    protected readonly encodeFormats = ['HTML', 'HTML named', 'JS', 'JS surrogate', 'CSS', 'UTF-8'] as const;
    protected readonly decodeFormats = ['Авто', 'HTML', 'JS', 'CSS', 'U+'] as const;
    protected readonly formatOptions = computed<readonly string[]>(() => (this.mode() === 'encode' ? this.encodeFormats : this.decodeFormats));
    protected readonly formatValue = computed(() => (this.mode() === 'encode' ? this.encodeFormat() : this.decodeFormat()));
    protected readonly options = computed<UnicodeEncodeOptions>(() => ({
        latin: this.latin(),
        trimLeadingZeros: this.trimLeadingZeros(),
        escapeHtml: this.escapeHtml(),
        format: this.encodeFormatValue(),
    }));
    protected readonly result = computed(() =>
        this.mode() === 'encode' ? encodeUnicode(this.text(), this.options()) : decodeUnicode(this.text(), this.decodeFormatValue()),
    );
    protected readonly resultTitle = computed(() => (this.mode() === 'encode' ? 'Результат' : 'Декодированный текст'));
    protected readonly resultFilename = computed(() => (this.mode() === 'encode' ? 'unicode.txt' : 'decoded.txt'));
    protected readonly stats = computed(() => textStats(this.text()));

    protected readonly query = signal('');
    protected readonly activeSection = signal<number | null>(null);
    protected readonly sectionTitles = UNICODE_SECTIONS.map((section) => section.title);
    protected readonly sections = computed<readonly UnicodeSection[]>(() => this.filterSections(this.query()));

    protected readonly inspectText = signal('');
    protected readonly inspectStats = computed(() => textStats(this.inspectText()));
    protected readonly inspectRows = computed(() => graphemes(this.inspectText()).slice(0, 100).map(inspectGrapheme));
    protected readonly inspectTruncated = computed(() => graphemes(this.inspectText()).length > 100);

    protected readonly copied = signal<string | null>(null);

    private timer: ReturnType<typeof setTimeout> | null = null;

    protected selectTab(index: number): void {
        const target = TABS[index];

        if (target) {
            void this.router.navigateByUrl(target.path);
        }
    }

    protected setMode(mode: ConvertMode): void {
        this.mode.set(mode);
    }

    protected setFormat(value: string): void {
        if (this.mode() === 'encode') {
            this.encodeFormat.set(value);
        } else {
            this.decodeFormat.set(value);
        }
    }

    protected selectSection(index: number | null): void {
        this.activeSection.set(index);
    }

    protected htmlValues(item: UnicodeSymbol): readonly string[] {
        return item.html.length ? item.html : [encodeUnicode(item.symbol, { format: 'html' })];
    }

    protected codeValues(item: UnicodeSymbol): readonly string[] {
        return item.code.length ? item.code : [codePointsLabel(item.symbol)];
    }

    protected displaySymbol(item: UnicodeSymbol): string {
        return this.displayValue(item.symbol);
    }

    protected displayValue(symbol: string): string {
        return '' === symbol || INVISIBLE.test(symbol) ? '∅' : symbol;
    }

    protected symbolTitle(item: UnicodeSymbol): string {
        const label = codePointsLabel(item.symbol);

        return '' === item.name ? `Символ ${label}` : `Символ ${item.name} (${label})`;
    }

    protected async copy(value: string): Promise<void> {
        if ('' === value) {
            return;
        }

        if (!(await copyText(value))) {
            return;
        }

        this.copied.set(value);

        if (null !== this.timer) {
            clearTimeout(this.timer);
        }

        this.timer = setTimeout(() => this.copied.set(null), 1500);
    }

    protected isCopied(value: string): boolean {
        return value === this.copied();
    }

    ngOnDestroy(): void {
        if (null !== this.timer) {
            clearTimeout(this.timer);
        }
    }

    private encodeFormatValue(): UnicodeEncodeFormat {
        switch (this.encodeFormat()) {
            case 'HTML named':
                return 'html-named';
            case 'JS':
                return 'js';
            case 'JS surrogate':
                return 'js-surrogate';
            case 'CSS':
                return 'css';
            case 'UTF-8':
                return 'utf8';
            default:
                return 'html';
        }
    }

    private decodeFormatValue(): UnicodeDecodeFormat {
        switch (this.decodeFormat()) {
            case 'HTML':
                return 'html';
            case 'JS':
                return 'js';
            case 'CSS':
                return 'css';
            case 'U+':
                return 'codepoint';
            default:
                return 'auto';
        }
    }

    private filterSections(query: string): readonly UnicodeSection[] {
        const value = this.normalizeQuery(query);
        const active = this.activeSection();
        const source = null === active ? UNICODE_SECTIONS : UNICODE_SECTIONS.slice(active, active + 1);

        if ('' === value) {
            return source;
        }

        return source
            .map((section) => {
                if (UnicodeTool.matches(section.title, value)) {
                    return section;
                }

                return {
                    ...section,
                    items: section.items.filter((item) => this.matchesItem(item, value)),
                };
            })
            .filter((section) => section.items.length > 0);
    }

    private matchesItem(item: UnicodeSymbol, query: string): boolean {
        if (/^[0-9a-f]{2,6}$/.test(query)) {
            const isCodePoint = Array.from(item.symbol).some((char) => (char.codePointAt(0) ?? -1).toString(16).includes(query));

            if (isCodePoint) {
                return true;
            }
        }

        return [item.symbol, item.name, ...this.htmlValues(item), ...this.codeValues(item)].some((field) => UnicodeTool.matches(field, query));
    }

    private normalizeQuery(query: string): string {
        let value = query.trim().toLowerCase();

        value = value.replace(/^u\+/, '');
        value = value.replace(/^&#x?/, '').replace(/;$/, '');
        value = value.replace(/^\\u\{?/, '').replace(/\}$/, '');
        value = value.replace(/^\\/, '');

        return value.trim();
    }

    private static tabIndex(data: Record<string, unknown>): number {
        const id = data['tab'] ?? 'convert';
        const index = TABS.findIndex((tab) => tab.id === id);

        return index < 0 ? 0 : index;
    }

    /**
     * Нечёткий поиск: подсстрока или совпадение по началу слова
     * (последнее нужно для запросов вроде «стрел» → «Стрелки»).
     */
    private static matches(text: string, query: string): boolean {
        const value = text.toLowerCase();

        if (value.includes(query)) {
            return true;
        }

        if (query.length < 4) {
            return false;
        }

        const prefix = query.slice(0, 4);

        return (value.match(/[\p{L}\p{N}]+/gu) ?? []).some((word) => word.startsWith(prefix));
    }
}
