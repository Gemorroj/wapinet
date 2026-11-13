import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiIcon, TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';

import { GistResponse } from '../../../api/generated/models';
import { GistsApi } from '../../../core/gists-api';
import { describeError } from '../../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { CodeHighlightDirective } from '../../../shared/code-highlight/code-highlight.directive';
import { RelativeDatePipe } from '../../../shared/relative-date/relative-date.pipe';
import { UserAvatar } from '../../../shared/user-avatar/user-avatar';
import { VkComments } from '../../../shared/vk-comments/vk-comments';

@Component({
    imports: [
        Breadcrumbs,
        CodeHighlightDirective,
        RelativeDatePipe,
        RouterLink,
        TuiButton,
        TuiIcon,
        TuiLoader,
        TuiNotificationDirective,
        UserAvatar,
        VkComments,
    ],
    selector: 'app-gist-detail',
    styleUrl: './gist-detail.css',
    templateUrl: './gist-detail.html',
})
export class GistDetail implements OnInit {
    private readonly gistsApi = inject(GistsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly gist = signal<GistResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly deleting = signal(false);
    protected readonly confirmingDelete = signal(false);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Блоги', link: '/gist' },
        { label: this.gist()?.subject ?? 'Запись' },
    ]);

    ngOnInit(): void {
        this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            void this.load(Number(params.get('id')));
        });
    }

    protected async delete(): Promise<void> {
        const gist = this.gist();

        if (null === gist) {
            return;
        }

        this.deleting.set(true);
        this.error.set(null);

        try {
            await this.gistsApi.delete(gist.id);
            await this.router.navigate(['/gist']);
        } catch (error) {
            this.error.set(describeError(error));
            this.confirmingDelete.set(false);
        } finally {
            this.deleting.set(false);
        }
    }

    private async load(id: number): Promise<void> {
        this.gist.set(null);
        this.error.set(null);

        if (!Number.isInteger(id) || id <= 0) {
            this.error.set('Сообщение не найдено.');

            return;
        }

        this.loading.set(true);

        try {
            const gist = await this.gistsApi.show(id);

            this.gist.set(gist);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
