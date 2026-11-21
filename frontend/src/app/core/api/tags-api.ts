import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { getApiTagsList } from '../../api/generated/functions';
import { TagFilterQuery, TagListResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class TagsApi {
    private readonly api = inject(Api);

    list(params: { filter?: TagFilterQuery; page?: number; perPage?: number } = {}): Promise<TagListResponse> {
        return this.api.invoke(getApiTagsList, params);
    }
}
