import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiInput, TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';
import { TuiChip } from '@taiga-ui/kit/components/chip';

import { TagListItem } from '../../../api/generated/models';
import { describeError } from '../../../core/api/problem-details';
import { TagsApi } from '../../../core/api/tags-api';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { Pagination } from '../../../shared/pagination/pagination';
import { readPage } from '../../../core/router/page-query';

@Component({
    imports: [Pagination, Breadcrumbs, FormsModule, RouterLink, TuiBadge, TuiButton, TuiChip, TuiInput, TuiLoader, TuiNotificationDirective],
    selector: 'app-tags',
    styleUrl: './tags.css',
    templateUrl: './tags.html',
})
export class Tags implements OnInit {
    private readonly tagsApi = inject(TagsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Файлообменник', link: '/files' }, { label: 'Теги' }];
    protected readonly items = signal<TagListItem[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);

    protected readonly perPage = 50;

    protected q = '';

    ngOnInit(): void {
        this.route.queryParamMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            this.q = params.get('q') ?? '';
            this.page.set(readPage(params));
            void this.load();
        });
    }

    protected async search(): Promise<void> {
        const q = this.q.trim();

        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: { q: '' === q ? null : q, page: null },
            queryParamsHandling: 'merge',
        });
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], { relativeTo: this.route, queryParams: { page }, queryParamsHandling: 'merge' });
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.tagsApi.list({
                page: this.page(),
                perPage: this.perPage,
                filter: { q: this.q === '' ? undefined : this.q },
            });

            this.items.set(result.items);
            this.total.set(result.total);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
