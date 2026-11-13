import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiCell, TuiIcon, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiTitle } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { combineLatest } from 'rxjs';

import { GistListItem } from '../../api/generated/models';
import { Auth } from '../../core/auth';
import { GistsApi, GistSort } from '../../core/gists-api';
import { readPage } from '../../core/page-query';
import { describeError } from '../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { FilterDrawer } from '../../shared/filter-drawer/filter-drawer';
import { Pagination } from '../../shared/pagination/pagination';
import { RelativeDatePipe } from '../../shared/relative-date/relative-date.pipe';
import { UserAvatar } from '../../shared/user-avatar/user-avatar';

interface SortOption {
    readonly value: GistSort;
    readonly label: string;
}

const SORTS: SortOption[] = [
    { value: 'newest', label: 'Сначала новые' },
    { value: 'oldest', label: 'Сначала старые' },
    { value: 'relevance', label: 'По релевантности' },
];

@Component({
    imports: [
        FilterDrawer,
        Pagination,
        Breadcrumbs,
        FormsModule,
        RelativeDatePipe,
        RouterLink,
        TuiBadge,
        TuiButton,
        TuiCell,
        TuiChip,
        TuiIcon,
        TuiInput,
        TuiLoader,
        TuiNotificationDirective,
        TuiTextfield,
        TuiTitle,
        UserAvatar,
    ],
    selector: 'app-gists',
    styleUrl: './gists.css',
    templateUrl: './gists.html',
})
export class Gists implements OnInit {
    protected readonly auth = inject(Auth);
    private readonly gistsApi = inject(GistsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly sorts = SORTS;
    protected readonly username = signal<string | null>(null);
    protected readonly items = signal<GistListItem[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly perPage = signal(20);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly searchText = signal('');
    protected readonly sort = signal<GistSort>('newest');
    protected readonly author = signal('');
    protected readonly mine = signal(false);
    protected readonly filtersOpen = signal(false);

    protected readonly title = computed(() => {
        const username = this.username();

        if (null !== username) {
            return `Блог: ${username}`;
        }

        return 'Блоги';
    });
    protected readonly subtitle = computed(() => {
        const username = this.username();

        if (null !== username) {
            return `Записи пользователя ${username}.`;
        }

        return 'Личные записи пользователей сайта.';
    });
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const items: Breadcrumb[] = [
            { label: 'Главная', link: '/' },
            { label: 'Блоги', link: '/gist' },
        ];
        const username = this.username();

        if (null !== username) {
            items.push({ label: username });
        }

        return items;
    });
    protected readonly activeFilterCount = computed(() => {
        const sort = Number('newest' !== this.sort());

        return sort + (this.username() ? 0 : Number(this.mine() || '' !== this.author().trim()));
    });

    ngOnInit(): void {
        combineLatest([this.route.paramMap, this.route.queryParamMap])
            .pipe(takeUntilDestroyed(this.destroyRef))
            .subscribe(([params, query]) => {
                this.username.set(params.get('username'));
                this.searchText.set(query.get('q') ?? '');
                this.author.set(query.get('user') ?? '');
                this.mine.set('1' === query.get('mine'));

                const sort = query.get('sort');

                this.sort.set(sort === 'oldest' || sort === 'relevance' ? sort : 'newest');
                this.page.set(readPage(query));
                void this.load();
            });
    }

    protected async search(): Promise<void> {
        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: this.filterQueryParams(),
            queryParamsHandling: 'merge',
        });
    }

    protected selectSort(option: SortOption): void {
        this.sort.set(option.value);
    }

    protected async applyFilters(): Promise<void> {
        this.filtersOpen.set(false);

        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: this.filterQueryParams(),
            queryParamsHandling: 'merge',
        });
    }

    protected async resetFilters(): Promise<void> {
        this.sort.set('newest');
        this.author.set('');
        this.mine.set(false);
        this.filtersOpen.set(false);

        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: { sort: null, user: null, mine: null, page: null },
            queryParamsHandling: 'merge',
        });
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: { page },
            queryParamsHandling: 'merge',
        });
    }

    private filterQueryParams(): {
        q: string | null;
        sort: GistSort | null;
        user: string | null;
        mine: string | null;
        page: null;
    } {
        return {
            q: this.searchText().trim() || null,
            sort: 'newest' === this.sort() ? null : this.sort(),
            user: this.username() || this.mine() ? null : this.author().trim() || null,
            mine: !this.username() && this.mine() ? '1' : null,
            page: null,
        };
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.gistsApi.list({
                filter: {
                    q: this.searchText().trim() || null,
                    user: this.username() ?? (this.mine() ? (this.auth.user()?.username ?? null) : this.author().trim() || null),
                },
                page: this.page(),
                sort: this.sort(),
            });

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
