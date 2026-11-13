import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import { deleteApiGistsDelete, getApiGistsList, getApiGistsShow, patchApiGistsUpdate, postApiGistsCreate } from '../api/generated/functions';
import { GistCreateRequest, GistFilterQuery, GistListResponse, GistResponse, GistUpdateRequest } from '../api/generated/models';

export type GistSort = 'newest' | 'oldest' | 'relevance';

@Injectable({ providedIn: 'root' })
export class GistsApi {
    private readonly api = inject(Api);

    list(params: { filter?: GistFilterQuery; page?: number; perPage?: number; sort?: GistSort } = {}): Promise<GistListResponse> {
        return this.api.invoke(getApiGistsList, params);
    }

    show(id: number): Promise<GistResponse> {
        return this.api.invoke(getApiGistsShow, { id: String(id) });
    }

    create(body: GistCreateRequest): Promise<GistResponse> {
        return this.api.invoke(postApiGistsCreate, { body });
    }

    update(id: number, body: GistUpdateRequest): Promise<GistResponse> {
        return this.api.invoke(patchApiGistsUpdate, { id: String(id), body });
    }

    async delete(id: number): Promise<void> {
        await this.api.invoke(deleteApiGistsDelete, { id: String(id) });
    }
}
