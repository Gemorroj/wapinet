import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import { postApiHashCompute } from '../api/generated/functions';
import { HashResponse } from '../api/generated/models';
import { HashAlgorithmId } from './hash';

@Injectable({ providedIn: 'root' })
export class HashApi {
    private readonly api = inject(Api);

    hashUrl(url: string, algorithms: readonly HashAlgorithmId[]): Promise<HashResponse> {
        return this.api.invoke(postApiHashCompute, { body: { url, algorithms: [...algorithms] } });
    }
}
