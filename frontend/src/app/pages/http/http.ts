import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TuiButton, TuiCheckbox, TuiDataList, TuiDropdown, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiTitle } from '@taiga-ui/core';
import { TuiDataListWrapper } from '@taiga-ui/kit/components/data-list-wrapper';
import { TuiSelect } from '@taiga-ui/kit/components/select';
import { TuiChevron } from '@taiga-ui/kit/directives/chevron';
import { TuiCardLarge } from '@taiga-ui/layout';

import { HttpCallResponse as HttpToolResponse } from '../../api/generated/models';
import { copyText } from '../../core/browser/clipboard';
import { buildCurl, parseCurl } from './curl';
import { saveBlob } from '../../core/browser/download';
import { formatBytes } from '../../core/format/format-bytes';
import { HttpApi } from '../../core/api/http-api';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { CodeBlock } from '../../shared/code-block/code-block';
import { CodeField } from '../../shared/code-field/code-field';
import { ToolResult } from '../../shared/tool-result/tool-result';

@Component({
    imports: [
        Breadcrumbs,
        CodeBlock,
        CodeField,
        FormsModule,
        TuiButton,
        TuiCardLarge,
        TuiCheckbox,
        TuiChevron,
        TuiDataList,
        TuiDataListWrapper,
        TuiDropdown,
        TuiInput,
        TuiLoader,
        TuiNotificationDirective,
        TuiSelect,
        TuiTextfield,
        TuiTitle,
        ToolResult,
    ],
    selector: 'app-http',
    styleUrl: './http.css',
    templateUrl: './http.html',
})
export class HttpTool {
    private readonly httpApi = inject(HttpApi);
    private loadSequence = 0;

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'HTTP-клиент' }];
    protected readonly methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE'];
    protected readonly methodOptions = [...this.methods, 'Другой'];
    protected readonly method = signal('GET');
    protected readonly customMethod = signal(false);
    protected readonly methodOption = computed(() => (this.customMethod() ? 'Другой' : this.method()));
    protected readonly url = signal('');
    protected readonly headerPresets = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ',
        'Cache-Control: no-cache',
        'User-Agent: Wapinet HTTP Client',
        'Cookie: ',
        'Origin: https://example.com',
        'Referer: https://example.com/',
        'X-Requested-With: XMLHttpRequest',
        'If-None-Match: *',
        'Range: bytes=0-1023',
    ];
    protected readonly headerMenuOpen = signal(false);
    protected readonly headers = signal('Accept: */*\nCache-Control: no-cache\nUser-Agent: Wapinet HTTP Client');
    protected readonly body = signal('');
    protected readonly base64Body = signal(false);
    protected readonly timeout = signal(10);
    protected readonly httpVersions = ['Авто', '1.1', '2', '3'];
    protected readonly httpVersion = signal('Авто');
    protected readonly verifyTls = signal(true);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly result = signal<HttpToolResponse | null>(null);
    protected readonly responseHeaders = computed(() => (this.result()?.headers ?? []).map((header) => `${header.name}: ${header.value}`).join('\n'));
    protected readonly headerError = computed(() => {
        const lines = this.headers()
            .split(/\r?\n/)
            .map((line) => line.trim())
            .filter(Boolean);

        for (const line of lines) {
            const position = line.indexOf(':');

            if (position <= 0) {
                return `Строка должна быть в формате «Name: value»: ${line}`;
            }
        }

        return null;
    });
    protected readonly sizeLabel = computed(() => formatBytes(this.result()?.size ?? 0));
    protected readonly statusClass = computed(() => {
        const status = this.result()?.status ?? 0;

        if (status >= 200 && status < 300) {
            return 'http__status_ok';
        }

        if (status >= 300 && status < 400) {
            return 'http__status_warn';
        }

        return 'http__status_error';
    });
    protected readonly curlMode = signal<'import' | 'export' | null>(null);
    protected readonly curlInput = signal('');
    protected readonly curlError = signal<string | null>(null);
    protected readonly curlWarnings = signal<readonly string[]>([]);
    protected readonly curlCopied = signal(false);
    protected readonly curlExport = computed(() => {
        const version = this.httpVersion();

        return buildCurl({
            url: this.url(),
            method: this.method(),
            headers: this.headers(),
            body: this.body(),
            base64Body: this.base64Body(),
            timeout: this.timeout(),
            httpVersion: 'Авто' === version ? 'auto' : (version as '1.1' | '2' | '3'),
            verifyTls: this.verifyTls(),
        });
    });

    protected async send(): Promise<void> {
        const url = this.url().trim();

        if ('' === url) {
            this.error.set('Укажите URL.');
            return;
        }

        if (null !== this.headerError()) {
            return;
        }

        const sequence = ++this.loadSequence;

        this.loading.set(true);
        this.error.set(null);

        try {
            const result = await this.httpApi.send({
                url,
                method: this.method().trim() || 'GET',
                headers: this.headers().trim() || null,
                body: this.body() || null,
                bodyEncoding: this.base64Body() ? 'base64' : 'text',
                timeout: this.timeout(),
                httpVersion: 'Авто' === this.httpVersion() ? 'auto' : (this.httpVersion() as '1.1' | '2' | '3'),
                verifyTls: this.verifyTls(),
            });

            if (sequence === this.loadSequence) {
                this.result.set(result);
            }
        } catch (error) {
            if (sequence === this.loadSequence) {
                this.result.set(null);
                this.error.set(describeError(error));
            }
        } finally {
            if (sequence === this.loadSequence) {
                this.loading.set(false);
            }
        }
    }

    protected addHeader(template: string | null): void {
        this.headerMenuOpen.set(false);

        if (!template) {
            return;
        }

        const current = this.headers().trimEnd();

        this.headers.set((current ? current + '\n' : '') + template);
    }

    protected toggleCurl(mode: 'import' | 'export'): void {
        this.curlError.set(null);
        this.curlWarnings.set([]);
        this.curlCopied.set(false);
        this.curlMode.set(this.curlMode() === mode ? null : mode);
    }

    protected applyCurl(): void {
        const result = parseCurl(this.curlInput());

        this.curlWarnings.set(result.warnings);

        if (result.error || !result.request) {
            this.curlError.set(result.error ?? 'Не удалось разобрать команду cURL.');
            return;
        }

        const request = result.request;

        this.curlError.set(null);
        this.url.set(request.url);
        this.method.set(request.method);
        this.customMethod.set(!this.methods.includes(request.method));
        this.headers.set(request.headers);
        this.body.set(request.body);
        this.base64Body.set(request.base64Body);
        this.timeout.set(request.timeout);
        this.httpVersion.set('auto' === request.httpVersion ? 'Авто' : request.httpVersion);
        this.verifyTls.set(request.verifyTls);
        this.result.set(null);
    }

    protected async copyCurl(): Promise<void> {
        this.curlCopied.set(await copyText(this.curlExport().command));
    }

    protected setMethodOption(value: string | null): void {
        if (null === value || '' === value) {
            this.customMethod.set(false);
            this.method.set('GET');
            return;
        }

        if ('Другой' === value) {
            this.customMethod.set(true);
            this.method.set('');
            return;
        }

        this.customMethod.set(false);
        this.method.set(value);
    }

    protected normalizeMethod(): void {
        this.method.set(this.method().trim().toUpperCase() || 'GET');
    }

    protected follow(location: string): void {
        try {
            this.url.set(new URL(location, this.result()?.url ?? this.url()).toString());
        } catch {
            this.url.set(location);
        }

        void this.send();
    }

    protected downloadBinary(): void {
        const result = this.result();

        if (!result?.bodyBase64) {
            return;
        }

        const raw = atob(result.bodyBase64);
        const bytes = new Uint8Array(raw.length);

        for (let index = 0; index < raw.length; index++) {
            bytes[index] = raw.charCodeAt(index);
        }

        saveBlob(new Blob([bytes], { type: result.contentType ?? 'application/octet-stream' }), 'response.bin');
    }
}
