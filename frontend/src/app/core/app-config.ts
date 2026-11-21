import { Injectable, inject, signal } from '@angular/core';

import { Api } from '../api/generated/api';
import { getApiConfig } from '../api/generated/functions';
import { ConfigResponse } from '../api/generated/models';

@Injectable({ providedIn: 'root' })
export class AppConfigService {
    private readonly api = inject(Api);
    private readonly state = signal<ConfigResponse | null>(null);

    readonly config = this.state.asReadonly();

    async load(): Promise<ConfigResponse> {
        const current = this.state();

        if (current !== null) {
            return current;
        }

        const config = await this.api.invoke(getApiConfig);

        this.state.set(config);

        return config;
    }
}
