import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { getApiOnlineList } from '../../api/generated/functions';
import { OnlineListResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class OnlineApi {
    private readonly api = inject(Api);

    list(): Promise<OnlineListResponse> {
        return this.api.invoke(getApiOnlineList, {});
    }
}
