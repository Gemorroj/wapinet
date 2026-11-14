import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { getApiHomeShow } from '../../api/generated/functions';
import { HomeResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class HomeApi {
    private readonly api = inject(Api);

    stats(): Promise<HomeResponse> {
        return this.api.invoke(getApiHomeShow, {});
    }
}
