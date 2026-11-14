import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { of } from 'rxjs';

import { WhoisResponse } from '../../api/generated/models';
import { WhoisApi } from '../../core/api/whois-api';
import { Whois } from './whois';

describe('Whois', () => {
    const response = {
        query: 'example.com',
        kind: 'domain',
        protocol: 'rdap',
        server: 'https://rdap.example.test/',
        raw: '{"ldhName":"example.com"}',
        details: [
            { label: 'Домен', value: 'example.com' },
            { label: 'Handle', value: 'EXAMPLE-COM' },
            { label: 'Страна', value: 'UA' },
        ],
        statuses: ['active'],
        events: [{ action: 'Зарегистрирован', date: '2020-01-01T00:00:00Z' }],
        nameservers: ['ns1.example.com'],
        contacts: [{ role: 'registrar', name: 'Example Registrar', organization: null, email: 'registrar@example.com' }],
        dns: { records: [{ type: 'A', value: '1.2.3.4' }], ips: [], hosts: [], provider: 'Cloudflare' },
    } as WhoisResponse;
    const lookup = vi.fn().mockResolvedValue(response);
    const route = {
        queryParamMap: of(convertToParamMap({ query: 'example.com', protocol: 'auto' })),
    };

    beforeEach(async () => {
        lookup.mockClear();

        await TestBed.configureTestingModule({
            imports: [Whois],
            providers: [provideRouter([]), provideTaiga(), { provide: WhoisApi, useValue: { lookup } }, { provide: ActivatedRoute, useValue: route }],
        }).compileComponents();
    });

    it('loads the query from the url and renders the summary', async () => {
        const fixture = TestBed.createComponent(Whois);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(lookup).toHaveBeenCalledWith('example.com', 'auto');
        expect(text).toContain('Результат');
        expect(text).toContain('RDAP');
        expect(text).toContain('example.com');
        expect(text).toContain('EXAMPLE-COM');
        expect(text).toContain('active');
        expect(text).toContain('ns1.example.com');
        expect(text).toContain('Example Registrar');
        expect(text).toContain('Сырой ответ');
        expect(text).toContain('Cloudflare DNS');
        expect(fixture.nativeElement.querySelectorAll('app-info-hint').length).toBeGreaterThan(0);

        const flag = fixture.nativeElement.querySelector('.whois__details .whois__flag') as HTMLImageElement;

        expect(flag.src).toContain('/resources/taiga-ui/icons/flags/ua.svg');
    });

    it('shows days until expiry with a status tone', async () => {
        const expiryDate = new Date(Date.now() + 10 * 86_400_000).toISOString();

        lookup.mockResolvedValueOnce({
            ...response,
            statuses: ['active', 'clientHold', 'client delete prohibited'],
            events: [
                { action: 'Зарегистрирован', date: '2020-01-01T00:00:00Z' },
                { action: 'Истекает', date: expiryDate },
            ],
        });

        const fixture = TestBed.createComponent(Whois);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const expiry = fixture.nativeElement.querySelector('.whois__expiry') as HTMLElement;
        const statuses = fixture.nativeElement.querySelectorAll('.whois__status') as NodeListOf<HTMLElement>;

        expect(expiry.textContent).toContain('Осталось 10 дней');
        expect(expiry.className).toContain('whois__expiry_warn');
        expect(statuses[0].className).toContain('whois__status_ok');
        expect(statuses[1].className).toContain('whois__status_bad');
        expect(statuses[1].title).toContain('приостановлен');
        expect(statuses[2].className).toContain('whois__status_ok');
        expect(statuses[2].title).toContain('Запрет удаления');
    });

    it('marks an expired domain', async () => {
        lookup.mockResolvedValueOnce({
            ...response,
            statuses: ['pendingDelete'],
            events: [{ action: 'Истекает', date: new Date(Date.now() - 3 * 86_400_000).toISOString() }],
        });

        const fixture = TestBed.createComponent(Whois);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        const expiry = fixture.nativeElement.querySelector('.whois__expiry') as HTMLElement;

        expect(expiry.textContent).toContain('Просрочен на 3 дня');
        expect(expiry.className).toContain('whois__expiry_bad');
    });

    it('fills the query with the client IP', async () => {
        const fixture = TestBed.createComponent(Whois);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        lookup.mockResolvedValueOnce({ ...response, query: '8.8.8.8', kind: 'ipv4' });

        const button = [...fixture.nativeElement.querySelectorAll('button')].find((item: HTMLButtonElement) =>
            item.textContent?.includes('Мой IP'),
        ) as HTMLButtonElement;

        button.click();
        await fixture.whenStable();
        fixture.detectChanges();

        const input = fixture.nativeElement.querySelector('input[name="query"]') as HTMLInputElement;

        expect(input.value).toBe('8.8.8.8');
    });
});
