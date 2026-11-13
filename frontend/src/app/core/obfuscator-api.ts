import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import { getApiPhpObfuscatorInfo, postApiPhpObfuscatorObfuscate } from '../api/generated/functions';
import { ObfuscatorInfoResponse, ObfuscatorLevel, ObfuscatorResponse } from '../api/generated/models';
import { CodeSource } from './code-source';

@Injectable({ providedIn: 'root' })
export class ObfuscatorApi {
    private readonly api = inject(Api);

    obfuscate(source: CodeSource, removeComments: boolean, level: ObfuscatorLevel = 'max'): Promise<ObfuscatorResponse> {
        return this.api.invoke(postApiPhpObfuscatorObfuscate, {
            body: {
                code: source.code ?? null,
                url: source.url ?? null,
                removeComments,
                level,
            },
        });
    }

    info(): Promise<ObfuscatorInfoResponse> {
        return this.api.invoke(getApiPhpObfuscatorInfo, {});
    }
}
