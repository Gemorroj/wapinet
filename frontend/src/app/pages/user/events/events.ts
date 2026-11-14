import { NgTemplateOutlet } from '@angular/common';
import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiCell, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';

import { EventResponse } from '../../../api/generated/models';
import { describeError } from '../../../core/api/problem-details';
import { UsersApi } from '../../../core/api/users-api';
import { AccountNav } from '../account-nav/account-nav';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { RelativeDatePipe } from '../../../shared/relative-date/relative-date.pipe';
import { Pagination } from '../../../shared/pagination/pagination';
import { readPage } from '../../../core/router/page-query';

@Component({
    imports: [
        AccountNav,
        NgTemplateOutlet,
        Pagination,
        Breadcrumbs,
        RelativeDatePipe,
        RouterLink,
        TuiCell,
        TuiIcon,
        TuiLoader,
        TuiNotificationDirective,
        TuiTitle,
    ],
    selector: 'app-user-events',
    styleUrl: './events.css',
    templateUrl: './events.html',
})
export class UserEvents implements OnInit {
    private readonly usersApi = inject(UsersApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Профиль', link: '/user/profile' }, { label: 'События' }];
    protected readonly items = signal<EventResponse[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly perPage = signal(10);
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

    protected isSpaUrl(url: string | null | undefined): boolean {
        if (!url) {
            return false;
        }

        return url.startsWith('/');
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const data = await this.usersApi.events(this.page());

            this.items.set(data.items);
            this.total.set(data.total);
            this.perPage.set(data.perPage);
            this.page.set(data.page);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
