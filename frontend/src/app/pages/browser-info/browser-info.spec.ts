import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { BrowserInfoResponse } from '../../api/generated/models';
import { BrowserInfoApi } from '../../core/api/browser-info-api';
import { BrowserInfo } from './browser-info';

describe('BrowserInfo', () => {
    const response = {
        ip: '127.0.0.1',
        ipHost: 'localhost',
        proxy: null,
        proxyHost: null,
        phoneNumber: null,
        userAgent: 'Test browser',
        remotePort: '12345',
        remoteUser: null,
        referer: null,
        encoding: 'gzip',
        cache: null,
        connection: 'keep-alive',
        via: null,
        httpProtocol: 'HTTP/1.1',
        accept: 'text/html',
        language: 'ru-RU',
        charset: null,
        dnt: null,
        method: 'GET',
        uri: '/browser_info',
        serverName: 'wapinet.loc',
        serverPort: '80',
        https: false,
        geoDatabase: {
            type: 'GeoLite2-City',
            buildDate: '2026-11-06',
        },
        proxyGeo: [],
        webrtcGeo: [],
        vpn: {
            suspicious: true,
            torExit: true,
            proxyHeaders: false,
            countryMismatch: true,
            blacklisted: true,
            signals: ['IP является выходной нодой Tor.', 'Страна определения (DE) не совпадает со страной регистрации сети (NL).'],
        },
        insight: {
            ip: '127.0.0.1',
            kind: 'hosting',
            organization: 'Example Hosting',
            asn: 64500,
            network: '127.0.0.0/8',
            geo: {
                continent: 'Европа',
                continentCode: 'EU',
                country: 'Германия',
                countryCode: 'DE',
                region: 'Берлин',
                city: 'Берлин',
                postalCode: '10115',
                latitude: 52.52,
                longitude: 13.405,
                accuracyRadius: 20,
                timezone: 'Europe/Berlin',
                registeredCountry: 'Нидерланды',
                registeredCountryCode: 'NL',
                inEuropeanUnion: true,
                network: '185.220.101.0/25',
            },
            reputation: { appears: true, torExit: true },
        },
        headers: {
            'user-agent': ['Test browser'],
            authorization: ['[скрыто]'],
        },
    } as BrowserInfoResponse;
    const load = vi.fn().mockResolvedValue(response);

    beforeEach(async () => {
        load.mockClear();

        await TestBed.configureTestingModule({
            imports: [BrowserInfo],
            providers: [{ provide: BrowserInfoApi, useValue: { load } }, provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders browser, connection, geo and reputation blocks', async () => {
        const fixture = TestBed.createComponent(BrowserInfo);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Информация о браузере');
        expect(text).toContain('User-Agent');
        expect(text).toContain(navigator.userAgent);
        expect(text).toContain('127.0.0.1 (localhost)');
        expect(text).toContain('HTTP/1.1');
        expect(text).toContain('Геолокация по IP');
        expect(text).toContain('MaxMind GeoLite2 City');
        expect(text).toContain('сборка 6 ноября 2026');
        expect(text).toContain('Германия (DE)');
        expect(text).toContain('Берлин');
        expect(text).toContain('52.52, 13.405');
        expect(text).toContain('185.220.101.0/25');

        const flag = fixture.nativeElement.querySelector('.browser-info__flag') as HTMLImageElement;

        expect(flag.src).toContain('/resources/taiga-ui/icons/flags/de.svg');
        expect(text).toContain('Сеть и репутация');
        expect(text).toContain('Хостинг');
        expect(text).toContain('Example Hosting');
        expect(text).toContain('выходной нодой Tor');
        expect(text).toContain('HTTP-заголовки');
        expect(text).toContain('authorization');
        expect(text).toContain('[скрыто]');
        expect(text).toContain('Canvas-отпечаток');
        expect(text).toContain('Аудио-отпечаток');
        expect(text).toContain('WebRTC');

        const info = fixture.nativeElement.querySelector('app-info-hint button') as HTMLButtonElement;

        expect(info.getAttribute('aria-label')).toContain('Источник данных');
        expect(fixture.nativeElement.querySelector('a[href*="query=127.0.0.1"]')).toBeTruthy();
        expect(fixture.nativeElement.querySelector('app-tool-result')).toBeTruthy();
    });

    it('adds geo information for proxy addresses', async () => {
        load.mockResolvedValueOnce({
            ...response,
            proxy: '8.8.8.8',
            proxyHost: 'dns.google',
            proxyGeo: [
                {
                    ip: '8.8.8.8',
                    geo: {
                        country: 'США',
                        countryCode: 'US',
                        region: 'Калифорния',
                        city: 'Маунтин-Вью',
                    },
                },
            ],
        });

        const fixture = TestBed.createComponent(BrowserInfo);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('8.8.8.8 (dns.google) — США (US), Маунтин-Вью');
    });
});
