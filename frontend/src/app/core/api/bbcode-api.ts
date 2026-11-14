import { Injectable, inject } from '@angular/core';

import { Api } from '../../api/generated/api';
import { postApiBbcodePreview } from '../../api/generated/functions';
import { BbcodePreviewResponse } from '../../api/generated/models';

@Injectable({ providedIn: 'root' })
export class BbcodeApi {
    private readonly api = inject(Api);

    preview(text: string): Promise<BbcodePreviewResponse> {
        return this.api.invoke(postApiBbcodePreview, { body: { text } });
    }
}
