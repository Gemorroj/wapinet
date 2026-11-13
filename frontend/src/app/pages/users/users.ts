import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { TuiButton, TuiIcon, TuiInput, TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';

import { UserListItem } from '../../api/generated/models';
import { describeError } from '../../core/problem-details';
import { UsersApi } from '../../core/users-api';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { Pagination } from '../../shared/pagination/pagination';
import { UserList } from '../../shared/user-list/user-list';
import { readPage } from '../../core/page-query';

@Component({
    imports: [Pagination, Breadcrumbs, FormsModule, TuiButton, TuiIcon, TuiInput, TuiLoader, TuiNotificationDirective, UserList],
    selector: 'app-users',
    styleUrl: './users.css',
    templateUrl: './users.html',
})
export class Users implements OnInit {
    private readonly usersApi = inject(UsersApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Пользователи' }];
    protected readonly items = signal<UserListItem[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly perPage = signal(10);
    protected readonly search = signal('');
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);

    ngOnInit(): void {
        this.route.queryParamMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            this.search.set(params.get('search') ?? '');
            this.page.set(readPage(params));
            void this.load();
        });
    }

    protected async submitSearch(): Promise<void> {
        await this.router.navigate([], {
            queryParams: { search: this.search().trim() || null, page: null },
            queryParamsHandling: 'merge',
        });
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], { queryParams: { page }, queryParamsHandling: 'merge' });
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const data = await this.usersApi.list({ filter: { search: this.search().trim() || undefined }, page: this.page() });

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
