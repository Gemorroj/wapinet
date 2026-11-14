import { Injectable, inject, signal } from '@angular/core';

import { HomeResponse } from '../../api/generated/models';
import { HomeApi } from './home-api';

/**
 * Site counters are used by the home page and by the footer, so they are
 * requested once per application session and shared through a signal.
 */
@Injectable({ providedIn: 'root' })
export class SiteStats {
    private readonly homeApi = inject(HomeApi);
    private readonly state = signal<HomeResponse | null>(null);
    private pending: Promise<void> | null = null;

    readonly value = this.state.asReadonly();

    load(): Promise<void> {
        this.pending ??= this.homeApi
            .stats()
            .then((stats) => this.state.set(stats))
            .catch(() => undefined);

        return this.pending;
    }
}
