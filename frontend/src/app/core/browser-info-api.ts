import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import { getApiBrowserInfoShow } from '../api/generated/functions';
import { BrowserInfoResponse } from '../api/generated/models';

@Injectable({ providedIn: 'root' })
export class BrowserInfoApi {
    private readonly api = inject(Api);

    load(webrtc: readonly string[] = []): Promise<BrowserInfoResponse> {
        return this.api.invoke(getApiBrowserInfoShow, webrtc.length ? { webrtc: webrtc.join(',') } : {});
    }
}
