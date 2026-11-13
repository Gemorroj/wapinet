import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute } from '@angular/router';
import { TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';

import { NewsResponse } from '../../../api/generated/models';
import { NewsApi } from '../../../core/news-api';
import { describeError } from '../../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { CodeHighlightDirective } from '../../../shared/code-highlight/code-highlight.directive';
import { RelativeDatePipe } from '../../../shared/relative-date/relative-date.pipe';
import { VkComments } from '../../../shared/vk-comments/vk-comments';

@Component({
    imports: [Breadcrumbs, CodeHighlightDirective, RelativeDatePipe, TuiLoader, TuiNotificationDirective, VkComments],
    selector: 'app-news-detail',
    styleUrl: './news-detail.css',
    templateUrl: './news-detail.html',
})
export class NewsDetail implements OnInit {
    private readonly newsApi = inject(NewsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly news = signal<NewsResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Новости', link: '/news' },
        { label: this.news()?.subject ?? 'Новость' },
    ]);

    ngOnInit(): void {
        this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            void this.load(Number(params.get('id')));
        });
    }

    private async load(id: number): Promise<void> {
        this.news.set(null);
        this.error.set(null);

        if (!Number.isInteger(id) || id <= 0) {
            this.error.set('Новость не найдена.');

            return;
        }

        this.loading.set(true);

        try {
            const news = await this.newsApi.show(id);

            this.news.set(news);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
