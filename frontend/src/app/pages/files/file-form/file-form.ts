import { Component, OnInit, ViewChild, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiCheckbox, TuiDropdown, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiTitle } from '@taiga-ui/core';
import { TuiDataListWrapper } from '@taiga-ui/kit/components/data-list-wrapper';
import { TuiInputChip } from '@taiga-ui/kit/components/input-chip';
import { TuiCardLarge } from '@taiga-ui/layout';

import { FileResponse } from '../../../api/generated/models';
import { AppConfigService } from '../../../core/app-config';
import { Auth } from '../../../core/auth';
import { FilesApi } from '../../../core/files-api';
import { formatBytes } from '../../../core/format-bytes';
import { describeError } from '../../../core/problem-details';
import { TagsApi } from '../../../core/tags-api';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { BbcodeEditor } from '../../../shared/bbcode-editor/bbcode-editor';
import { Captcha } from '../../../shared/captcha/captcha';
import { FileSource } from '../../../shared/file-source/file-source';

@Component({
    imports: [
        Breadcrumbs,
        BbcodeEditor,
        Captcha,
        FileSource,
        FormsModule,
        RouterLink,
        TuiButton,
        TuiCardLarge,
        TuiCheckbox,
        TuiDataListWrapper,
        TuiDropdown,
        TuiInput,
        TuiInputChip,
        TuiLoader,
        TuiNotificationDirective,
        TuiTextfield,
        TuiTitle,
    ],
    selector: 'app-file-form',
    styleUrl: './file-form.css',
    templateUrl: './file-form.html',
})
export class FileForm implements OnInit {
    protected readonly auth = inject(Auth);
    protected readonly appConfig = inject(AppConfigService);
    private readonly filesApi = inject(FilesApi);
    private readonly tagsApi = inject(TagsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);

    @ViewChild(Captcha) private captcha?: Captcha;

    protected readonly fileId = signal<number | null>(null);
    protected readonly editing = computed(() => this.fileId() !== null);
    protected readonly currentFile = signal<FileResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly saving = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const items: Breadcrumb[] = [
            { label: 'Главная', link: '/' },
            { label: 'Файлообменник', link: '/files' },
        ];

        if (this.editing()) {
            items.push({ label: this.currentFile()?.name ?? 'Файл', link: `/files/${this.fileId()}` });
            items.push({ label: 'Редактирование' });
        } else {
            items.push({ label: 'Загрузка' });
        }

        return items;
    });

    protected readonly description = signal('');
    protected readonly tags = signal<string[]>([]);
    protected readonly suggestions = signal<string[]>([]);
    protected readonly password = signal('');
    protected readonly removePassword = signal(false);
    protected readonly selectedFile = signal<File | null>(null);
    protected readonly selectedFiles = computed<File[]>(() => {
        const file = this.selectedFile();

        return file ? [file] : [];
    });
    protected readonly sourceUrl = signal('');
    protected readonly captchaActive = signal(false);
    protected readonly captchaToken = signal('');

    private tagsTimer: ReturnType<typeof setTimeout> | null = null;

    async ngOnInit(): Promise<void> {
        const id = Number(this.route.snapshot.paramMap.get('id'));

        if (Number.isInteger(id) && id > 0) {
            this.fileId.set(id);
            this.loading.set(true);

            try {
                const file = await this.filesApi.show(id);

                this.currentFile.set(file);
                this.description.set(file.description);
                this.tags.set([...file.tags]);

                if (!file.canEdit) {
                    this.error.set('Недостаточно прав для редактирования файла.');
                }
            } catch (error) {
                this.error.set(describeError(error));
            } finally {
                this.loading.set(false);
            }
        }

        try {
            await this.appConfig.load();
        } catch {
            if (!this.auth.authenticated()) {
                this.error.set('Не удалось загрузить настройки капчи.');
            }
        }
    }

    protected selectFile(files: File[]): void {
        this.selectedFile.set(files[0] ?? null);
    }

    protected async save(): Promise<void> {
        const id = this.fileId();
        const file = this.selectedFile();
        const url = this.sourceUrl().trim();
        const description = this.description().trim();

        if (null === id && null === file && '' === url) {
            this.error.set('Выберите файл или укажите ссылку.');
            return;
        }

        if (null === id && '' === description) {
            this.error.set('Укажите описание файла.');
            return;
        }

        const isAnonymous = !this.auth.authenticated();
        const config = this.appConfig.config();

        if (isAnonymous) {
            if (null === config) {
                this.error.set('Не удалось загрузить настройки капчи.');
                return;
            }

            if (config.captcha.enabled) {
                if (!this.captchaActive()) {
                    this.captchaActive.set(true);
                    this.error.set('Подтвердите, что вы не робот.');

                    return;
                }

                if ('' === this.captchaToken()) {
                    this.error.set('Подтвердите, что вы не робот.');

                    return;
                }
            }
        }

        this.saving.set(true);
        this.error.set(null);

        try {
            if (null === id) {
                await this.filesApi.create({
                    file,
                    url: '' === url ? null : url,
                    description,
                    tags: this.tags(),
                    password: '' === this.password() ? null : this.password(),
                    captchaToken: isAnonymous ? this.captchaToken() || null : null,
                });

                await this.router.navigate(['/files'], { queryParams: { uploaded: 1 } });
            } else {
                await this.filesApi.update(id, {
                    description,
                    tags: this.tags(),
                    password: this.removePassword() ? '' : '' === this.password() ? null : this.password(),
                    file,
                    url: '' === url ? null : url,
                });

                await this.router.navigate(['/files', id]);
            }
        } catch (error) {
            if (isAnonymous && config?.captcha?.enabled) {
                this.captchaToken.set('');
                this.captcha?.reset();
            }

            this.error.set(describeError(error));
        } finally {
            this.saving.set(false);
        }
    }

    protected async cancel(): Promise<void> {
        const id = this.fileId();

        await this.router.navigate(null !== id ? ['/files', id] : ['/files']);
    }

    protected setTags(tags: string[]): void {
        this.tags.set(tags);
        this.suggestions.set([]);
        this.clearTagsTimer();
    }

    protected onTagInput(event: Event): void {
        const query = (event.target as HTMLInputElement).value.trim();

        this.clearTagsTimer();

        if ('' === query) {
            this.suggestions.set([]);

            return;
        }

        this.tagsTimer = setTimeout(() => void this.loadSuggestions(query), 250);
    }

    private async loadSuggestions(query: string): Promise<void> {
        try {
            const result = await this.tagsApi.list({ filter: { q: query }, page: 1, perPage: 10 });
            const selected = new Set(this.tags());

            this.suggestions.set(result.items.map((tag) => tag.name).filter((name) => !selected.has(name)));
        } catch {
            this.suggestions.set([]);
        }
    }

    private clearTagsTimer(): void {
        if (null !== this.tagsTimer) {
            clearTimeout(this.tagsTimer);
            this.tagsTimer = null;
        }
    }

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }

    protected isImage(file: FileResponse): boolean {
        return file.mimeType.startsWith('image/');
    }

    protected previewUrl(file: FileResponse): string {
        return file.links.thumbnailUrl ?? file.links.url;
    }

    protected onCaptchaFailed(): void {
        this.error.set('Не удалось загрузить капчу. Обновите страницу.');
    }
}
