import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router } from '@angular/router';
import { combineLatest } from 'rxjs';
import { TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';

import { UserListItem } from '../../../api/generated/models';
import { describeError } from '../../../core/api/problem-details';
import { UsersApi } from '../../../core/api/users-api';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { Pagination } from '../../../shared/pagination/pagination';
import { UserList } from '../user-list/user-list';
import { readPage } from '../../../core/router/page-query';

@Component({
    imports: [Pagination, Breadcrumbs, TuiLoader, TuiNotificationDirective, UserList],
    selector: 'app-user-friends',
    templateUrl: './friends.html',
})
export class UserFriends implements OnInit {
    private readonly usersApi = inject(UsersApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly username = signal('');
    protected readonly items = signal<UserListItem[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly perPage = signal(10);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Пользователи', link: '/user/users' },
        { label: this.username(), link: `/user/profile/${this.username()}` },
        { label: 'Друзья' },
    ]);

    ngOnInit(): void {
        combineLatest([this.route.paramMap, this.route.queryParamMap])
            .pipe(takeUntilDestroyed(this.destroyRef))
            .subscribe(([params, query]) => {
                this.username.set(params.get('username') ?? '');
                this.page.set(readPage(query));
                void this.load();
            });
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], { queryParams: { page }, queryParamsHandling: 'merge' });
    }

    private async load(): Promise<void> {
        if ('' === this.username()) {
            return;
        }

        this.loading.set(true);
        this.error.set(null);

        try {
            const data = await this.usersApi.friends(this.username(), this.page());

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
