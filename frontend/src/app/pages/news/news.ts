import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiCell, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';

import { NewsListItemResponse } from '../../api/generated/models';
import { NewsApi } from '../../core/api/news-api';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { RelativeDatePipe } from '../../shared/relative-date/relative-date.pipe';
import { Pagination } from '../../shared/pagination/pagination';
import { readPage } from '../../core/router/page-query';

@Component({
    imports: [Pagination, Breadcrumbs, RelativeDatePipe, RouterLink, TuiCell, TuiLoader, TuiNotificationDirective, TuiTitle],
    selector: 'app-news',
    templateUrl: './news.html',
})
export class News implements OnInit {
    private readonly newsApi = inject(NewsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Новости' }];
    protected readonly items = signal<NewsListItemResponse[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly perPage = signal(20);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);

    ngOnInit(): void {
        this.route.queryParamMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            this.page.set(readPage(params));
            void this.load();
        });
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], { queryParams: { page }, queryParamsHandling: 'merge' });
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.newsApi.list(this.page());

            this.items.set(result.items);
            this.total.set(result.total);
            this.perPage.set(result.perPage);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
