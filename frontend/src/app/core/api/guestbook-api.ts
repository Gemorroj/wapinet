import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { getApiGuestbookInsight, getApiGuestbookList, postApiGuestbookCreate } from '../../api/generated/functions';
import { GuestbookCreateRequest, GuestbookListResponse, GuestbookMessageResponse, IpInsightResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class GuestbookApi {
    private readonly api = inject(Api);

    list(page = 1): Promise<GuestbookListResponse> {
        return this.api.invoke(getApiGuestbookList, { page });
    }

    create(body: GuestbookCreateRequest): Promise<GuestbookMessageResponse> {
        return this.api.invoke(postApiGuestbookCreate, { body });
    }

    insight(id: number): Promise<IpInsightResponse> {
        return this.api.invoke(getApiGuestbookInsight, { id: String(id) });
    }
}
