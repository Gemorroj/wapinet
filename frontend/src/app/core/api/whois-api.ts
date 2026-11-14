import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { postApiWhoisLookup } from '../../api/generated/functions';
import { WhoisResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class WhoisApi {
    private readonly api = inject(Api);

    lookup(query: string, protocol: 'auto' | 'rdap' | 'whois'): Promise<WhoisResponse> {
        return this.api.invoke(postApiWhoisLookup, { body: { query, protocol } });
    }
}
