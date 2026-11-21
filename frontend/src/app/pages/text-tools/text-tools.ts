import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Data, Router, RouterLink } from '@angular/router';
import { TuiNotificationDirective } from '@taiga-ui/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';
import { TuiCardLarge } from '@taiga-ui/layout';

import { TEXT_TOOL_GROUPS, TEXT_TOOL_OPERATIONS, TextToolError, TextToolGroupId, convertText } from './text-tools-codec';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { CodeBlock } from '../../shared/code-block/code-block';
import { CodeField } from '../../shared/code-field/code-field';
import { ToolResult } from '../../shared/tool-result/tool-result';

interface TextToolTab {
    readonly id: TextToolGroupId;
    readonly label: string;
    readonly title: string;
    readonly path: string;
}

const TEXT_TOOL_TABS: readonly TextToolTab[] = [
    { id: 'encoding', label: 'Кодирование', title: 'Кодирование', path: '/text-tools' },
    { id: 'data', label: 'Данные', title: 'Данные', path: '/text-tools/data' },
    { id: 'text', label: 'Текст', title: 'Текст', path: '/text-tools/text' },
];

@Component({
    imports: [Breadcrumbs, CodeBlock, CodeField, RouterLink, TuiCardLarge, TuiChip, TuiNotificationDirective, TuiTabs, ToolResult],
    selector: 'app-text-tools',
    styleUrl: './text-tools.css',
    templateUrl: './text-tools.html',
})
export class TextTools implements OnInit {
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly tabs = TEXT_TOOL_TABS;
    protected readonly groupIndex = signal(0);
    protected readonly group = computed<TextToolGroupId>(() => TEXT_TOOL_GROUPS[this.groupIndex()]?.id ?? 'encoding');
    protected readonly operations = computed(() => TEXT_TOOL_OPERATIONS.filter((operation) => operation.group === this.group()));
    protected readonly operation = signal('base64-encode');
    protected readonly selected = computed(() => TEXT_TOOL_OPERATIONS.find((operation) => operation.id === this.operation()) ?? TEXT_TOOL_OPERATIONS[0]);
    protected readonly text = signal('');
    protected readonly pageTitle = computed(() => TEXT_TOOL_TABS[this.groupIndex()]?.title ?? TEXT_TOOL_TABS[0].title);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Инструменты текста', link: 0 === this.groupIndex() ? undefined : '/text-tools' },
        { label: this.pageTitle() },
    ]);
    protected readonly result = computed<{ value: string; error: string | null }>(() => {
        if ('' === this.text()) {
            return { value: '', error: null };
        }

        try {
            return { value: convertText(this.operation(), this.text()), error: null };
        } catch (error) {
            return {
                value: '',
                error: error instanceof TextToolError ? error.message : 'Не удалось преобразовать данные.',
            };
        }
    });
    protected readonly hasResult = computed(() => '' !== this.text() && null === this.result().error);
    protected readonly resultLanguage = computed(() => {
        const id = this.operation();

        return ['json-format', 'json-minify', 'json-escape', 'json-unescape', 'query-to-json', 'url-parse'].includes(id) ? 'json' : null;
    });

    ngOnInit(): void {
        this.route.data.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((data) => {
            const index = TextTools.tabIndex(data);

            this.groupIndex.set(index);
            this.setDefaultOperation(index);
        });
    }

    protected selectGroup(index: number): void {
        const tab = TEXT_TOOL_TABS[index];

        if (!tab) {
            return;
        }

        this.groupIndex.set(index);
        this.setDefaultOperation(index);
        void this.router.navigateByUrl(tab.path);
    }

    protected selectOperation(id: string): void {
        this.operation.set(id);
    }

    private setDefaultOperation(index: number): void {
        const first = TEXT_TOOL_OPERATIONS.find((operation) => operation.group === TEXT_TOOL_GROUPS[index]?.id);

        if (first) {
            this.operation.set(first.id);
        }
    }

    private static tabIndex(data: Data): number {
        const index = TEXT_TOOL_GROUPS.findIndex((group) => group.id === data['tab']);

        return index < 0 ? 0 : index;
    }
}
