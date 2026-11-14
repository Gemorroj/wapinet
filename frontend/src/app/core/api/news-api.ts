import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { getApiNewsList, getApiNewsShow } from '../../api/generated/functions';
import { NewsListResponse, NewsResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class NewsApi {
    private readonly api = inject(Api);

    list(page = 1): Promise<NewsListResponse> {
        return this.api.invoke(getApiNewsList, { page });
    }

    show(id: number): Promise<NewsResponse> {
        return this.api.invoke(getApiNewsShow, { id: String(id) });
    }
}
