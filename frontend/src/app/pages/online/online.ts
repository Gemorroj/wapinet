import { DatePipe } from '@angular/common';
import { Component, OnDestroy, OnInit, inject, signal } from '@angular/core';
import { TuiButton, TuiCell, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';

import { OnlineItemResponse } from '../../api/generated/models';
import { OnlineApi } from '../../core/api/online-api';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { RelativeDatePipe } from '../../shared/relative-date/relative-date.pipe';

@Component({
    imports: [Breadcrumbs, DatePipe, RelativeDatePipe, TuiButton, TuiCell, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle],
    selector: 'app-online',
    styleUrl: './online.css',
    templateUrl: './online.html',
})
export class Online implements OnInit, OnDestroy {
    private readonly onlineApi = inject(OnlineApi);
    private timer: ReturnType<typeof setInterval> | null = null;

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Онлайн' }];
    protected readonly items = signal<OnlineItemResponse[]>([]);
    protected readonly count = signal(0);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly lastUpdated = signal<Date | null>(null);

    async ngOnInit(): Promise<void> {
        await this.load();
        this.timer = setInterval(() => void this.load(), 30_000);
    }

    ngOnDestroy(): void {
        if (null !== this.timer) {
            clearInterval(this.timer);
        }
    }

    protected async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);

        try {
            const data = await this.onlineApi.list();
            this.items.set(data.items);
            this.count.set(data.count);
            this.lastUpdated.set(new Date());
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
