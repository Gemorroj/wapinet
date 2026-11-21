import { Component, ElementRef, ViewChild, computed, inject, input, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TuiButton, TuiDialog, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTextfield } from '@taiga-ui/core';
import { TuiTextarea } from '@taiga-ui/kit/components/textarea';

import { BbcodeApi } from '../../core/api/bbcode-api';
import { describeError } from '../../core/api/problem-details';
import { CodeHighlightDirective } from '../code-highlight/code-highlight.directive';

interface HelpItem {
    readonly tag: string;
    readonly meaning: string;
}

interface HelpGroup {
    readonly title: string;
    readonly items: readonly HelpItem[];
}

const HELP_GROUPS: readonly HelpGroup[] = [
    {
        title: 'Текст',
        items: [
            { tag: '[b]жирный[/b]', meaning: 'жирный' },
            { tag: '[i]курсив[/i]', meaning: 'курсив' },
            { tag: '[u]подчёркнутый[/u]', meaning: 'подчёркнутый' },
            { tag: '[s]зачёркнутый[/s]', meaning: 'зачёркнутый' },
            { tag: '[color=red]цветной[/color]', meaning: 'цвет' },
            { tag: '[size=5]крупный[/size]', meaning: 'размер, 1–7' },
        ],
    },
    {
        title: 'Ссылки и медиа',
        items: [
            { tag: '[url=адрес]текст[/url]', meaning: 'ссылка' },
            { tag: '[url]адрес[/url]', meaning: 'ссылка с адресом в тексте' },
            { tag: '[email]почта[/email]', meaning: 'e-mail' },
            { tag: '[img]адрес[/img]', meaning: 'картинка' },
            { tag: '[youtube]ID[/youtube]', meaning: 'видео YouTube' },
            { tag: '[vk]ссылка[/vk]', meaning: 'видео ВКонтакте' },
            { tag: '[rutube]ссылка[/rutube]', meaning: 'видео Rutube' },
        ],
    },
    {
        title: 'Блоки',
        items: [
            { tag: '[quote]цитата[/quote]', meaning: 'цитата' },
            { tag: '[quote=Автор]цитата[/quote]', meaning: 'цитата с автором' },
            { tag: '[code=php]код[/code]', meaning: 'код с подсветкой' },
            { tag: '[spoiler]текст[/spoiler]', meaning: 'спойлер' },
            { tag: '[list][*]пункт[/list]', meaning: 'список' },
            { tag: '[table][tr][td]ячейка[/td][/tr][/table]', meaning: 'таблица' },
        ],
    },
];

@Component({
    imports: [CodeHighlightDirective, FormsModule, TuiButton, TuiDialog, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTextarea, TuiTextfield],
    selector: 'app-bbcode-editor',
    styleUrl: './bbcode-editor.css',
    templateUrl: './bbcode-editor.html',
})
export class BbcodeEditor {
    readonly label = input.required<string>();
    readonly name = input.required<string>();
    readonly value = input.required<string>();
    readonly valueChange = output<string>();
    readonly limit = input<number | null>(null);
    readonly rows = input(8);
    readonly maxRows = input(20);
    readonly placeholder = input('');

    @ViewChild('textarea') private textarea?: ElementRef<HTMLTextAreaElement>;

    private readonly api = inject(BbcodeApi);

    protected readonly helpGroups = HELP_GROUPS;
    protected readonly helpOpen = signal(false);
    protected readonly mode = signal<'edit' | 'preview'>('edit');
    protected readonly html = signal<string | null>(null);
    protected readonly previewedText = signal<string | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly canPreview = computed(() => '' !== this.value().trim());

    focus(): void {
        this.mode.set('edit');
        setTimeout(() => this.textarea?.nativeElement.focus());
    }

    protected async switchMode(): Promise<void> {
        if ('preview' === this.mode()) {
            this.mode.set('edit');

            return;
        }

        await this.preview();
    }

    private async preview(): Promise<void> {
        const text = this.value().trim();

        if ('' === text) {
            return;
        }

        this.mode.set('preview');

        if (null !== this.html() && this.previewedText() === text) {
            return;
        }

        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.api.preview(text);

            this.html.set(result.html);
            this.previewedText.set(text);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
