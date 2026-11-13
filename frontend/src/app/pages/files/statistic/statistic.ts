import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiCell, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';

import { FileStatisticsResponse } from '../../../api/generated/models';
import { FilesApi } from '../../../core/files-api';
import { formatBytes } from '../../../core/format-bytes';
import { describeError } from '../../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';

@Component({
    imports: [Breadcrumbs, RouterLink, TuiBadge, TuiCell, TuiLoader, TuiNotificationDirective, TuiTitle],
    selector: 'app-file-statistic',
    templateUrl: './statistic.html',
})
export class FileStatistic implements OnInit {
    private readonly filesApi = inject(FilesApi);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Файлообменник', link: '/files' }, { label: 'Статистика' }];
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

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }
}
