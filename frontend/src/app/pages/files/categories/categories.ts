import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';
import { TuiChip } from '@taiga-ui/kit/components/chip';

import { FileStatisticsResponse } from '../../../api/generated/models';
import { FilesApi } from '../../../core/files-api';
import { describeError } from '../../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';

const CATEGORIES: Array<{ key: string; label: string }> = [
    { key: 'video', label: 'Видео' },
    { key: 'audio', label: 'Аудио' },
    { key: 'image', label: 'Картинки' },
    { key: 'text', label: 'Текстовые файлы' },
    { key: 'office', label: 'Офисные документы' },
    { key: 'archive', label: 'Архивы' },
    { key: 'android', label: 'Приложения Android' },
    { key: 'java', label: 'Приложения Java' },
];

@Component({
    imports: [Breadcrumbs, RouterLink, TuiBadge, TuiChip, TuiLoader, TuiNotificationDirective],
    selector: 'app-file-categories',
    templateUrl: './categories.html',
})
export class FileCategories implements OnInit {
    private readonly filesApi = inject(FilesApi);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Файлообменник', link: '/files' }, { label: 'Категории' }];
    protected readonly categories = CATEGORIES;
    protected readonly statistics = signal<FileStatisticsResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);

    async ngOnInit(): Promise<void> {
        this.loading.set(true);

        try {
            this.statistics.set(await this.filesApi.statistics());
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected count(key: string): number | null {
        return this.statistics()?.categories[key] ?? null;
    }
}
