import { Injectable, inject } from '@angular/core';

import { Api } from '../api/generated/api';
import { getApiPhpValidatorInfo, postApiCssValidatorValidate, postApiHtmlValidatorValidate, postApiPhpValidatorValidate } from '../api/generated/functions';
import { PhpValidatorInfoResponse, ProfileType, ValidatorResponse, WarningType } from '../api/generated/models';
import { CodeSource } from './code-source';

export type ValidatorType = 'php' | 'html' | 'css';

export interface CssValidatorOptions {
    profile: ProfileType;
    warnings: WarningType;
}

@Injectable({ providedIn: 'root' })
export class ValidatorApi {
    private readonly api = inject(Api);

    validate(type: ValidatorType, source: CodeSource, css?: CssValidatorOptions): Promise<ValidatorResponse> {
        const body = {
            code: source.code ?? null,
            url: source.url ?? null,
        };

        if ('php' === type) {
            return this.api.invoke(postApiPhpValidatorValidate, { body });
        }

        if ('html' === type) {
            return this.api.invoke(postApiHtmlValidatorValidate, { body });
        }

        return this.api.invoke(postApiCssValidatorValidate, {
            body: {
                ...body,
                profile: css?.profile ?? null,
                warnings: css?.warnings ?? null,
            },
        });
    }

    phpInfo(): Promise<PhpValidatorInfoResponse> {
        return this.api.invoke(getApiPhpValidatorInfo, {});
    }
}
