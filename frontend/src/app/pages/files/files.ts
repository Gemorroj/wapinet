import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, ParamMap, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiCell, TuiDropdown, TuiIcon, TuiInput, TuiLoader, TuiNotificationDirective, TuiTitle, tuiItemsHandlersProvider } from '@taiga-ui/core';
import { TuiAvatar } from '@taiga-ui/kit/components/avatar';
import { TuiBadge } from '@taiga-ui/kit/components/badge';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiComboBox } from '@taiga-ui/kit/components/combo-box';
import { TuiDataListWrapper } from '@taiga-ui/kit/components/data-list-wrapper';
import { TuiNativeSelect } from '@taiga-ui/kit/components/select';
import { TuiChevron } from '@taiga-ui/kit/directives/chevron';

import { Auth } from '../../core/auth';
import { FileListItem } from '../../api/generated/models';
import { saveBlob } from '../../core/download';
import { fileIcon } from '../../core/file-icon';
import { FilesApi } from '../../core/files-api';
import { formatBytes } from '../../core/format-bytes';
import { formatDuration } from '../../core/format-duration';
import { describeError } from '../../core/problem-details';
import { TagsApi } from '../../core/tags-api';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { FilterDrawer } from '../../shared/filter-drawer/filter-drawer';
import { RelativeDatePipe } from '../../shared/relative-date/relative-date.pipe';

type Category = 'video' | 'audio' | 'image' | 'text' | 'office' | 'archive' | 'android' | 'java' | 'windows' | 'playlist' | 'torrent' | 'other';
type DateFilter = 'all' | 'today' | 'yesterday';
type Sort = 'newest' | 'oldest' | 'name' | 'views' | 'relevance';

interface FilterOption {
    value: string | null;
    label: string;
}

interface CategoryOption extends FilterOption {
    value: Category | null;
}

const CATEGORIES: CategoryOption[] = [
    { value: null, label: 'Все' },
    { value: 'image', label: 'Изображения' },
    { value: 'video', label: 'Видео' },
    { value: 'audio', label: 'Аудио' },
    { value: 'text', label: 'Текст' },
    { value: 'office', label: 'Документы' },
    { value: 'archive', label: 'Архивы' },
    { value: 'android', label: 'Android' },
    { value: 'java', label: 'Java' },
    { value: 'windows', label: 'Windows' },
    { value: 'playlist', label: 'Плейлисты' },
    { value: 'torrent', label: 'Торренты' },
    { value: 'other', label: 'Другое' },
];

const DATES: Array<{ value: DateFilter; label: string }> = [
    { value: 'all', label: 'За всё время' },
    { value: 'today', label: 'Сегодня' },
    { value: 'yesterday', label: 'Вчера' },
];

const SORTS: Array<{ value: Sort; label: string }> = [
    { value: 'newest', label: 'Сначала новые' },
    { value: 'oldest', label: 'Сначала старые' },
    { value: 'name', label: 'По имени' },
    { value: 'views', label: 'По просмотрам' },
    { value: 'relevance', label: 'По релевантности' },
];
import { Pagination } from '../../shared/pagination/pagination';
import { readPage } from '../../core/page-query';

@Component({
    imports: [
        FilterDrawer,
        Pagination,
        Breadcrumbs,
        RelativeDatePipe,
        FormsModule,
        RouterLink,
        TuiAvatar,
        TuiBadge,
        TuiButton,
        TuiCell,
        TuiChevron,
        TuiChip,
        TuiComboBox,
        TuiDataListWrapper,
        TuiDropdown,
        TuiIcon,
        TuiInput,
        TuiLoader,
        TuiNativeSelect,
        TuiNotificationDirective,
        TuiTitle,
    ],
    providers: [
        tuiItemsHandlersProvider<FilterOption | string>({
            stringify: signal((item) => (typeof item === 'string' ? item : item.label)),
            identityMatcher: signal((first, second) =>
                typeof first === 'string' || typeof second === 'string' ? first === second : first.value === second.value,
            ),
        }),
    ],
    selector: 'app-files',
    styleUrl: './files.css',
    templateUrl: './files.html',
})
export class Files implements OnInit {
    protected readonly auth = inject(Auth);
    private readonly filesApi = inject(FilesApi);
    private readonly tagsApi = inject(TagsApi);
    private readonly router = inject(Router);
    private readonly route = inject(ActivatedRoute);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Файлообменник' }];
    protected readonly categories = CATEGORIES;
    protected readonly dates = DATES;
    protected readonly sorts = SORTS;
    protected readonly category = signal<Category | null>(null);
    protected readonly date = signal<DateFilter>('all');
    protected readonly sort = signal<Sort>('newest');
    protected readonly categoryOption = computed(() => CATEGORIES.find((item) => item.value === this.category()) ?? CATEGORIES[0]);
    protected readonly dateOption = computed(() => DATES.find((item) => item.value === this.date()) ?? DATES[0]);
    protected readonly sortOption = computed(() => SORTS.find((item) => item.value === this.sort()) ?? SORTS[0]);

    protected readonly items = signal<FileListItem[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly notice = signal<string | null>(null);
    protected readonly pendingDelete = signal<number | null>(null);
    protected readonly hiddenCount = signal<number | null>(null);

    protected readonly perPage = 20;
    protected readonly isAdmin = this.auth.isAdmin;

    protected q = '';
    protected tag = '';
    protected user = '';
    protected mine = false;
    protected hiddenFilter = false;

    protected readonly filtersOpen = signal(false);
    protected readonly tagSuggestions = signal<string[]>([]);

    private tagTimer: ReturnType<typeof setTimeout> | null = null;
    private tagRequest = 0;

    ngOnInit(): void {
        this.destroyRef.onDestroy(() => this.clearTagTimer());

        this.route.queryParamMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            this.readQueryParams(params);
            void this.load();
        });
    }

    protected async search(): Promise<void> {
        await this.navigateWithFilters();
    }

    protected activeFilterCount(): number {
        return [
            this.category() !== null,
            this.date() !== 'all',
            this.sort() !== 'newest',
            this.tag.trim() !== '',
            this.user.trim() !== '',
            this.mine,
            this.hiddenFilter,
        ].filter(Boolean).length;
    }

    protected async applyFilters(): Promise<void> {
        this.filtersOpen.set(false);
        await this.search();
    }

    protected async setCategoryOption(option: FilterOption): Promise<void> {
        this.category.set(option.value as Category | null);
        await this.navigateWithFilters();
    }

    protected async setDateOption(option: FilterOption): Promise<void> {
        this.date.set(option.value as DateFilter);
        await this.navigateWithFilters();
    }

    protected async setSortOption(option: FilterOption): Promise<void> {
        this.sort.set(option.value as Sort);
        await this.navigateWithFilters();
    }

    protected onTagInput(event: Event): void {
        const query = (event.target as HTMLInputElement).value.trim();

        this.clearTagTimer();

        this.tagTimer = setTimeout(() => void this.loadTagSuggestions(query), 250);
    }

    protected onTagFocus(): void {
        if (0 === this.tagSuggestions().length) {
            void this.loadTagSuggestions('');
        }
    }

    protected onTagChange(value: string | null): void {
        this.tag = value ?? '';
    }

    private async loadTagSuggestions(query: string): Promise<void> {
        const request = ++this.tagRequest;

        try {
            const result = await this.tagsApi.list({
                filter: '' === query ? {} : { q: query },
                page: 1,
                perPage: 10,
            });

            if (request === this.tagRequest) {
                this.tagSuggestions.set(result.items.map((tag) => tag.name));
            }
        } catch {
            if (request === this.tagRequest) {
                this.tagSuggestions.set([]);
            }
        }
    }

    private clearTagTimer(): void {
        if (null !== this.tagTimer) {
            clearTimeout(this.tagTimer);
            this.tagTimer = null;
        }
    }

    protected async toggleMine(): Promise<void> {
        if (!this.auth.authenticated()) {
            await this.router.navigate(['/login'], { queryParams: { returnUrl: '/files' } });
            return;
        }

        this.mine = !this.mine;
        await this.navigateWithFilters();
    }

    protected async toggleHidden(): Promise<void> {
        if (!this.isAdmin()) {
            return;
        }

        if (this.hiddenFilter) {
            await this.router.navigate(['/files']);
            return;
        }

        this.hiddenFilter = true;
        await this.navigateWithFilters();
    }

    protected async resetFilters(): Promise<void> {
        if (this.hiddenFilter) {
            await this.router.navigate(['/files']);
            return;
        }

        this.q = '';
        this.category.set(null);
        this.date.set('all');
        this.tag = '';
        this.user = '';
        this.sort.set('newest');
        this.mine = false;
        this.tagSuggestions.set([]);
        this.filtersOpen.set(false);
        await this.navigateWithFilters();
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: { page },
            queryParamsHandling: 'merge',
        });
    }

    protected async publish(file: FileListItem): Promise<void> {
        try {
            await this.filesApi.update(file.id, { hidden: false });
            await this.load();
        } catch (error) {
            this.error.set(describeError(error));
        }
    }

    protected async loadHiddenCount(): Promise<void> {
        if (!this.isAdmin()) {
            return;
        }

        try {
            const result = await this.filesApi.list({ page: 1, perPage: 1, filter: { hidden: true } });
            this.hiddenCount.set(result.total);
        } catch {
            this.hiddenCount.set(null);
        }
    }

    protected async confirmDelete(file: FileListItem): Promise<void> {
        try {
            await this.filesApi.delete(file.id);
            this.pendingDelete.set(null);
            await this.load();
        } catch (error) {
            this.error.set(describeError(error));
        }
    }

    protected async download(file: FileListItem): Promise<void> {
        try {
            saveBlob(await this.filesApi.download(file.id), file.name);
        } catch (error) {
            this.error.set(describeError(error));
        }
    }

    protected size(bytes: number): string {
        return formatBytes(bytes);
    }

    protected icon(file: FileListItem): string {
        return fileIcon(file.category, file.mimeType, file.name);
    }

    protected duration(seconds: unknown): string {
        return formatDuration(Number(seconds));
    }

    protected metaNumber(file: FileListItem, key: string): number | null {
        const meta = file.meta;

        if (!meta || Array.isArray(meta)) {
            return null;
        }

        const value = meta[key];

        return typeof value === 'number' ? value : null;
    }

    private readQueryParams(params: ParamMap): void {
        this.q = params.get('q') ?? '';
        this.page.set(readPage(params));

        const category = params.get('category');
        this.category.set(category !== null && CATEGORIES.some((item) => item.value === category) ? (category as Category) : null);

        const date = params.get('date');
        this.date.set(date === 'today' || date === 'yesterday' ? date : 'all');
        this.tag = params.get('tag') ?? '';
        this.user = params.get('user') ?? '';
        this.hiddenFilter = this.route.snapshot.data['hidden'] === true || params.get('hidden') === '1';

        const sort = params.get('sort');
        this.sort.set(sort !== null && SORTS.some((item) => item.value === sort) ? (sort as Sort) : 'newest');
        this.mine = params.get('mine') === '1';
        this.notice.set(params.get('uploaded') === '1' ? 'Файл загружен. Он появится в публичном списке после проверки администратором.' : null);
    }

    /**
     * Filter changes go through the URL so the page can be shared and the
     * browser history works exactly like on other list pages.
     */
    private async navigateWithFilters(): Promise<void> {
        await this.router.navigate([], {
            relativeTo: this.route,
            queryParams: {
                q: this.q.trim() || null,
                category: this.category(),
                date: 'all' === this.date() ? null : this.date(),
                sort: 'newest' === this.sort() ? null : this.sort(),
                tag: this.tag.trim() || null,
                user: this.user.trim() || null,
                mine: this.mine ? '1' : null,
                hidden: this.hiddenFilter ? '1' : null,
                uploaded: null,
                page: null,
            },
            queryParamsHandling: 'merge',
        });
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.filesApi.list({
                page: this.page(),
                perPage: this.perPage,
                sort: this.sort(),
                filter: {
                    q: this.q === '' ? undefined : this.q,
                    category: this.category() ?? undefined,
                    date: this.date(),
                    tag: this.tag.trim() === '' ? undefined : this.tag.trim(),
                    user: this.user.trim() === '' ? undefined : this.user.trim(),
                    hidden: this.hiddenFilter ? true : undefined,
                    mine: this.mine,
                },
            });

            this.items.set(result.items);
            this.total.set(result.total);

            if (this.isAdmin() && !this.hiddenFilter) {
                void this.loadHiddenCount();
            }
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
