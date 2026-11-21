import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { HttpCallResponse } from '../../api/generated/models';
import { HttpApi } from '../../core/api/http-api';
import { HttpTool } from './http';

describe('HttpTool', () => {
    const response = {
        status: 200,
        statusText: 'OK',
        httpVersion: '2',
        url: 'https://api.example.com/',
        location: null,
        time: 12.3,
        size: 42,
        truncated: false,
        contentType: 'application/json',
        bodyLanguage: 'json',
        binary: false,
        body: '{"ok":true}',
        bodyBase64: null,
        headers: [
            { name: 'content-type', value: 'application/json' },
            { name: 'cache-control', value: 'no-cache' },
        ],
    } as HttpCallResponse;
    const send = vi.fn().mockResolvedValue(response);

    beforeEach(async () => {
        send.mockClear();

        await TestBed.configureTestingModule({
            imports: [HttpTool],
            providers: [{ provide: HttpApi, useValue: { send } }, provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('sends the request and renders the response', async () => {
        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as { url: { set(value: string): void }; send(): Promise<void> };

        fixture.detectChanges();

        component.url.set('https://api.example.com/');
        await component.send();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(send).toHaveBeenCalledWith(expect.objectContaining({ url: 'https://api.example.com/', method: 'GET', bodyEncoding: 'text' }));
        expect(text).toContain('200 OK');
        expect(text).toContain('application/json');
        expect(text).toContain('{"ok":true}');
        expect(text).toContain('cache-control');

        const headerNames = fixture.nativeElement.querySelectorAll('.http__headers dt') as NodeListOf<HTMLElement>;
        const headerValues = fixture.nativeElement.querySelectorAll('.http__headers dd') as NodeListOf<HTMLElement>;

        expect(headerNames.length).toBe(2);
        expect(headerNames[0].textContent).toContain('content-type');
        expect(headerValues[0].textContent).toContain('application/json');
        expect(headerNames[1].textContent).toContain('cache-control');
        expect(headerValues[1].textContent).toContain('no-cache');
    });

    it('shows a problem message', async () => {
        send.mockRejectedValueOnce(new Error('Запросы к приватным и служебным адресам запрещены.'));

        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as { url: { set(value: string): void }; send(): Promise<void> };

        fixture.detectChanges();

        component.url.set('http://127.0.0.1/');
        await component.send();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Запросы к приватным и служебным адресам запрещены.');
    });

    it('blocks submit for a malformed header', () => {
        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as { headers: { set(value: string): void } };

        fixture.detectChanges();

        component.headers.set('Bad header');
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('.http__header-error')?.textContent).toContain('Name: value');
        expect((fixture.nativeElement.querySelector('button[type="submit"]') as HTMLButtonElement).disabled).toBe(true);
    });

    it('appends a header preset', () => {
        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as {
            addHeader(template: string): void;
            headers(): string;
        };

        fixture.detectChanges();

        component.addHeader('Content-Type: application/json');

        expect(component.headers()).toContain('Content-Type: application/json');
    });

    it('does not block unusual header names locally', () => {
        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as { headers: { set(value: string): void } };

        fixture.detectChanges();

        component.headers.set('Bad Name: value');
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('.http__header-error')).toBeNull();
    });

    it('imports a curl command into the form', () => {
        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as {
            curlInput: { set(value: string): void };
            applyCurl(): void;
            url(): string;
            method(): string;
            headers(): string;
            body(): string;
        };

        fixture.detectChanges();

        component.curlInput.set(`curl 'https://api.example.com/users' -H 'Accept: application/json' --data-raw '{"a":1}'`);
        component.applyCurl();

        expect(component.url()).toBe('https://api.example.com/users');
        expect(component.method()).toBe('POST');
        expect(component.headers()).toBe('Accept: application/json');
        expect(component.body()).toBe('{"a":1}');
    });

    it('shows a curl parse error', () => {
        const fixture = TestBed.createComponent(HttpTool);
        const component = fixture.componentInstance as unknown as {
            curlInput: { set(value: string): void };
            toggleCurl(mode: 'import' | 'export'): void;
            applyCurl(): void;
        };

        fixture.detectChanges();

        component.toggleCurl('import');
        component.curlInput.set(`curl 'https://example.com/`);
        component.applyCurl();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('.http__curl-error')?.textContent).toContain('кавычка');
    });
});
