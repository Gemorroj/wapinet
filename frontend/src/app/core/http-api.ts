import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import { postApiHttpSend } from '../api/generated/functions';
import { HttpCallRequest, HttpCallResponse } from '../api/generated/models';

@Injectable({ providedIn: 'root' })
export class HttpApi {
    private readonly api = inject(Api);

    send(body: HttpCallRequest): Promise<HttpCallResponse> {
        return this.api.invoke(postApiHttpSend, { body });
    }
}
