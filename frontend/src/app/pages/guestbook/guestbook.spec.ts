import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { AppConfigService } from '../../core/app-config';
import { Auth } from '../../core/auth/auth';
import { GuestbookApi } from '../../core/api/guestbook-api';
import { YandexCaptcha } from '../../core/auth/yandex-captcha';
import { Guestbook } from './guestbook';

describe('Guestbook', () => {
    const list = vi.fn().mockResolvedValue({
        items: [
            {
                id: 1,
                message: 'Привет',
                messageHtml: 'Привет',
                createdAt: '2026-01-01T12:00:00+00:00',
                user: { id: 1, username: 'alice' },
                avatarUrl: 'https://gravatar.com/avatar/1',
                isOnline: true,
                ip: null,
                browser: null,
            },
            {
                id: 2,
                message: 'Аноним',
                messageHtml: 'Аноним',
                createdAt: '2026-01-01T12:00:00+00:00',
                user: null,
                avatarUrl: 'https://gravatar.com/avatar/0',
                isOnline: false,
                ip: '1.2.3.4',
                browser: 'Firefox',
                countryCode: 'BY',
                countryName: 'Беларусь',
            },
        ],
        total: 1,
        page: 1,
        perPage: 10,
    });
    const create = vi.fn().mockResolvedValue({});
    const insight = vi.fn().mockResolvedValue({
        ip: '1.2.3.4',
        kind: 'hosting',
        organization: 'Example Hosting',
        asn: 64500,
        ptr: 'host.example',
        reputation: { appears: false, torExit: true },
        geo: { country: 'Беларусь', countryCode: 'BY' },
    });
    let configValue: { captcha: { enabled: boolean; siteKey: string } } | null = null;
    const config = { config: () => configValue, load: vi.fn().mockResolvedValue(undefined) };
    const auth = { authenticated: () => false, user: () => null };
    const captcha = { render: vi.fn().mockResolvedValue(1), reset: vi.fn(), destroy: vi.fn() };
    const queryParams = new BehaviorSubject(convertToParamMap({}));
    const route = { queryParamMap: queryParams.asObservable() };

    beforeEach(async () => {
        list.mockClear();
        create.mockClear();
        insight.mockClear();
        captcha.render.mockClear();
        captcha.reset.mockClear();
        captcha.destroy.mockClear();
        configValue = null;
        queryParams.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [Guestbook],
            providers: [
                { provide: GuestbookApi, useValue: { list, create, insight } },
                { provide: AppConfigService, useValue: config },
                { provide: Auth, useValue: auth },
                { provide: YandexCaptcha, useValue: captcha },
                provideRouter([]),
                { provide: ActivatedRoute, useValue: route },
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('renders messages', async () => {
        const fixture = TestBed.createComponent(Guestbook);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(list).toHaveBeenCalledWith(1);
        expect(fixture.nativeElement.textContent).toContain('alice');
        expect(fixture.nativeElement.textContent).toContain('Привет');

        fixture.destroy();
    });

    it('sends a message', async () => {
        const fixture = TestBed.createComponent(Guestbook);

        await fixture.whenStable();
        fixture.detectChanges();

        const textarea = fixture.nativeElement.querySelector('textarea[name="message"]') as HTMLTextAreaElement;

        textarea.value = 'Новое сообщение';
        textarea.dispatchEvent(new Event('input'));
        await fixture.whenStable();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('submit'));
        await fixture.whenStable();

        expect(create).toHaveBeenCalledWith({
            message: 'Новое сообщение',
            captchaToken: null,
            bCheck: null,
        });

        fixture.destroy();
    });

    it('shows anonymous details on demand', async () => {
        const fixture = TestBed.createComponent(Guestbook);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('.guestbook__anonymous-info')).toBeNull();

        const flag = fixture.nativeElement.querySelector('.guestbook__flag') as HTMLImageElement;

        expect(flag.src).toContain('/flags/by.svg');
        expect(flag.alt).toBe('Беларусь');
        expect(flag.title).toBe('Беларусь');

        const button = fixture.nativeElement.querySelector('.guestbook__anonymous') as HTMLButtonElement;

        button.click();
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const info = fixture.nativeElement.querySelector('.guestbook__anonymous-info') as HTMLElement;

        expect(info.textContent).toContain('1.2.3.4');
        expect(info.querySelector('a[href*="query=1.2.3.4"]')).toBeTruthy();
        expect(info.textContent).not.toContain('Беларусь');
        expect(info.textContent).toContain('Firefox');
        expect(info.textContent).toContain('Хостинг');
        expect(info.textContent).toContain('Tor');
        expect(info.textContent).toContain('Example Hosting');
        expect(info.textContent).toContain('64500');

        fixture.destroy();
    });

    it('quotes a message when replying', async () => {
        const fixture = TestBed.createComponent(Guestbook);

        await fixture.whenStable();
        fixture.detectChanges();

        const reply = [...(fixture.nativeElement.querySelectorAll('button') as NodeListOf<HTMLButtonElement>)].find(
            (button) => 'Ответить' === button.textContent?.trim(),
        );

        reply?.click();
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const textarea = fixture.nativeElement.querySelector('textarea[name="message"]') as HTMLTextAreaElement;

        expect(textarea.value).toContain('[quote=alice]Привет[/quote]');

        fixture.destroy();
    });

    it('loads the captcha only after the form is touched', async () => {
        configValue = { captcha: { enabled: true, siteKey: 'test' } };

        const fixture = TestBed.createComponent(Guestbook);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('app-captcha')).toBeNull();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('focusin', { bubbles: true }));
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('app-captcha')).toBeTruthy();

        fixture.destroy();
    });

    it('resets the captcha after a failed submit', async () => {
        configValue = { captcha: { enabled: true, siteKey: 'test' } };
        create.mockRejectedValueOnce(new Error('fail'));

        const fixture = TestBed.createComponent(Guestbook);

        await fixture.whenStable();
        fixture.detectChanges();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('focusin', { bubbles: true }));
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const component = fixture.componentInstance as unknown as {
            message: { set(value: string): void };
            captchaToken: { set(value: string): void };
            submit(): Promise<void>;
        };

        component.message.set('Новое сообщение');
        component.captchaToken.set('token');
        await component.submit();

        expect(captcha.reset).toHaveBeenCalledWith(1);

        fixture.destroy();
    });
});
