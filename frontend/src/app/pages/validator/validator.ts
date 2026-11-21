import { Component, DestroyRef, OnInit, ViewChild, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Data, Router } from '@angular/router';
import { TuiButton, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiTitle, tuiItemsHandlersProvider } from '@taiga-ui/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiSelect } from '@taiga-ui/kit/components/select';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';
import { TuiCardLarge } from '@taiga-ui/layout';

import { PhpValidatorInfoResponse, ProfileType, ValidatorResponse, WarningType } from '../../api/generated/models';
import { describeError } from '../../core/api/problem-details';
import { CssValidatorOptions, ValidatorApi, ValidatorType } from '../../core/api/validator-api';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { CodeSourcePanel } from '../../shared/code-source/code-source';
import { HelpSection } from '../../shared/help-section/help-section';
import { SourceView } from '../../shared/source-view/source-view';

interface ValidatorConfig {
    readonly id: ValidatorType;
    readonly title: string;
    readonly path: string;
    readonly codeLabel: string;
    readonly placeholder: string;
    readonly accept: string;
    readonly hint: string;
    readonly about: {
        readonly what: string;
        readonly not: string;
        readonly limits: string;
    };
}

interface ValidatorTab {
    readonly id: 'tool' | 'about';
    readonly label: string;
}

interface SelectOption<T extends string> {
    readonly value: T;
    readonly label: string;
}

const VALIDATORS: Record<ValidatorType, ValidatorConfig> = {
    php: {
        id: 'php',
        title: 'PHP-валидатор',
        path: '/php_validator',
        codeLabel: 'PHP-код',
        placeholder: '<?php\necho "Hello";',
        accept: '.php,.phtml,.txt',
        hint: 'Проверяется синтаксис; код не выполняется.',
        about: {
            what: 'Проверяется синтаксис PHP установленной на сервере версии. Код не выполняется.',
            not: 'Валидатор не проверяет логику, стиль и совместимость с другими версиями PHP.',
            limits: 'Синтаксис зависит от версии PHP на сервере. Если тега <?php нет, он добавляется автоматически.',
        },
    },
    html: {
        id: 'html',
        title: 'HTML-валидатор',
        path: '/html_validator',
        codeLabel: 'HTML-код',
        placeholder: '<!doctype html>\n<html lang="ru">…',
        accept: '.html,.htm,.xhtml,.txt',
        hint: 'Проверка выполняется сервисом W3C.',
        about: {
            what: 'Документ отправляется в W3C HTML Validator. Проверяются разметка, вложенность тегов и обязательные атрибуты.',
            not: 'Скрипты и внешние ресурсы не проверяются.',
            limits: 'Нужен доступ в интернет: проверка выполняется внешним сервисом W3C.',
        },
    },
    css: {
        id: 'css',
        title: 'CSS-валидатор',
        path: '/css_validator',
        codeLabel: 'CSS-код',
        placeholder: 'body {\n    color: red;\n}',
        accept: '.css,.txt',
        hint: 'Проверка выполняется сервисом W3C.',
        about: {
            what: 'Код отправляется в W3C CSS Validator. Профиль задаёт уровень CSS, а уровень предупреждений — их подробность.',
            not: 'HTML-разметка и скрипты не проверяются.',
            limits: 'Проверка выполняется внешним сервисом W3C; результат зависит от выбранного профиля.',
        },
    },
};

const TABS: readonly ValidatorTab[] = [
    { id: 'tool', label: 'Проверка' },
    { id: 'about', label: 'Справка' },
];

const PROFILES: readonly SelectOption<ProfileType>[] = [
    { value: 'css3', label: 'CSS3' },
    { value: 'mobile', label: 'Мобильные' },
    { value: 'css21', label: 'CSS 2.1' },
    { value: 'css2', label: 'CSS 2' },
    { value: 'css1', label: 'CSS 1' },
    { value: 'svg', label: 'SVG' },
    { value: 'svgbasic', label: 'SVG Basic' },
    { value: 'svgtiny', label: 'SVG Tiny' },
    { value: 'atsc-tv', label: 'Телевидение ATSC' },
    { value: 'tv', label: 'Телевидение' },
    { value: 'none', label: 'Без профиля' },
];

const WARNINGS: readonly SelectOption<WarningType>[] = [
    { value: '1', label: 'Обычные' },
    { value: '0', label: 'Только важные' },
    { value: '2', label: 'Все' },
    { value: 'no', label: 'Без предупреждений' },
];

@Component({
    imports: [
        Breadcrumbs,
        CodeSourcePanel,
        FormsModule,
        HelpSection,
        SourceView,
        TuiButton,
        TuiCardLarge,
        TuiChip,
        TuiLoader,
        TuiNotificationDirective,
        TuiSelect,
        TuiTabs,
        TuiTextfield,
        TuiTitle,
    ],
    providers: [
        tuiItemsHandlersProvider<SelectOption<string>>({
            stringify: signal((item) => item.label),
            identityMatcher: signal((first, second) => first.value === second.value),
        }),
    ],
    selector: 'app-validator',
    styleUrl: './validator.css',
    templateUrl: './validator.html',
})
export class ValidatorTool implements OnInit {
    private readonly api = inject(ValidatorApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    @ViewChild(CodeSourcePanel) private sourcePanel?: CodeSourcePanel;

    protected readonly tabs = TABS;
    protected readonly profileOptions = PROFILES;
    protected readonly warningOptions = WARNINGS;
    protected readonly type = signal<ValidatorType>('php');
    protected readonly tool = computed(() => VALIDATORS[this.type()]);
    protected readonly tabIndex = signal(0);
    protected readonly code = signal('');
    protected readonly result = signal<ValidatorResponse | null>(null);
    protected readonly filter = signal<'all' | 'error' | 'warning'>('all');
    protected readonly submittedCode = signal<string | null>(null);
    protected readonly phpInfo = signal<PhpValidatorInfoResponse | null>(null);
    protected readonly error = signal<string | null>(null);
    protected readonly loading = signal(false);
    protected readonly profile = signal(PROFILES[0]);
    protected readonly warning = signal(WARNINGS[0]);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const tool = this.tool();

        return 0 === this.tabIndex()
            ? [{ label: 'Главная', link: '/' }, { label: tool.title }]
            : [{ label: 'Главная', link: '/' }, { label: tool.title, link: tool.path }, { label: 'Справка' }];
    });
    protected readonly errorCount = computed(() => this.countMessages('error'));
    protected readonly warningCount = computed(() => this.countMessages('warning'));
    protected readonly messagesCount = computed(() => this.result()?.messages.length ?? 0);
    protected readonly filteredMessages = computed(() => {
        const messages = this.result()?.messages ?? [];
        const filter = this.filter();

        return 'all' === filter ? messages : messages.filter((message) => filter === message.severity);
    });
    protected readonly errorLines = computed(() => this.linesFor('error'));
    protected readonly warningLines = computed(() => this.linesFor('warning'));
    protected readonly sourceCode = computed(() => this.result()?.source ?? this.submittedCode());
    protected readonly phpVersion = computed(() => {
        const version = this.result()?.phpVersion ?? this.phpInfo()?.phpVersion ?? '';

        return '' === version ? '' : version.split('.').slice(0, 2).join('.');
    });

    ngOnInit(): void {
        this.route.data.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((data) => {
            const type = ValidatorTool.typeOf(data);

            if (type !== this.type()) {
                this.code.set('');
                this.sourcePanel?.reset();
                this.result.set(null);
                this.submittedCode.set(null);
                this.filter.set('all');
            }

            this.type.set(type);
            this.tabIndex.set('about' === data['tab'] ? 1 : 0);
            this.error.set(null);
        });

        void this.loadPhpInfo();
    }

    protected selectTab(index: number): void {
        const tab = TABS[index];

        if (!tab || index === this.tabIndex()) {
            return;
        }

        this.tabIndex.set(index);
        void this.router.navigateByUrl(this.tool().path + ('about' === tab.id ? '/about' : ''));
    }

    protected setCode(code: string): void {
        this.code.set(code);
        this.onSourceChanged();
    }

    protected onSourceChanged(): void {
        this.result.set(null);
        this.submittedCode.set(null);
        this.filter.set('all');
        this.error.set(null);
    }

    protected async validate(): Promise<void> {
        const source = await this.sourcePanel?.resolve();

        if (!source) {
            return;
        }

        this.loading.set(true);
        this.error.set(null);
        this.result.set(null);
        this.filter.set('all');
        this.submittedCode.set(source.code ?? null);

        try {
            this.result.set(await this.api.validate(this.type(), source, this.cssOptions()));
        } catch (error) {
            this.result.set(null);
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    private cssOptions(): CssValidatorOptions | undefined {
        return 'css' === this.type()
            ? {
                  profile: this.profile().value,
                  warnings: this.warning().value,
              }
            : undefined;
    }

    protected selectFilter(filter: 'all' | 'error' | 'warning'): void {
        this.filter.set(filter);
    }

    protected jumpToLine(line: number | null | undefined): void {
        if (null == line) {
            return;
        }

        document.getElementById(`validator-line-${line}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    protected profileLabel(value: string | null | undefined): string {
        return PROFILES.find((option) => option.value === value)?.label ?? value ?? '';
    }

    private linesFor(severity: 'error' | 'warning'): number[] {
        const lines = (this.result()?.messages ?? [])
            .filter((message) => severity === message.severity)
            .map((message) => message.line)
            .filter((line): line is number => null != line);

        return [...new Set(lines)];
    }

    private async loadPhpInfo(): Promise<void> {
        try {
            this.phpInfo.set(await this.api.phpInfo());
        } catch {
            // Справка остаётся читаемой без версии PHP.
        }
    }

    private countMessages(severity: 'error' | 'warning'): number {
        return this.result()?.messages.filter((message) => severity === message.severity).length ?? 0;
    }

    private static typeOf(data: Data): ValidatorType {
        const value = data['tool'];

        return 'html' === value || 'css' === value ? value : 'php';
    }
}
