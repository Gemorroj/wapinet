import { NgTemplateOutlet } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { BrowserGeoInfo, BrowserInfoResponse } from '../../api/generated/models';
import { BrowserInfoApi } from '../../core/api/browser-info-api';
import { capabilityRows } from '../../core/browser/capabilities';
import { utcOffsetNow } from '../../core/browser/clock';
import { countryFlagUrl, geoCountryLabel } from '../../core/browser/country-flag';
import { AudioFingerprint, WebRtcFingerprint, audioFingerprint, canvasFingerprint, webrtcFingerprint } from '../../core/browser/fingerprint';
import { MediaInfo, mediaSupport } from '../../core/browser/media';
import { permissionRows } from '../../core/browser/permissions';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { InfoHint } from '../../shared/info-hint/info-hint';
import { IpInsight } from '../../shared/ip-insight/ip-insight';
import { ToolResult } from '../../shared/tool-result/tool-result';

interface InfoRow {
    readonly flag?: string;
    readonly flagAlt?: string;
    readonly label: string;
    readonly link?: string;
    readonly source?: string;
    readonly value: string;
}

interface NetworkConnection {
    readonly downlink?: number;
    readonly effectiveType?: string;
    readonly rtt?: number;
    readonly saveData?: boolean;
    readonly type?: string;
}

interface UserAgentData {
    readonly brands?: Array<{ readonly brand: string; readonly version: string }>;
    readonly getHighEntropyValues?: (hints: string[]) => Promise<{
        architecture?: string;
        bitness?: string;
        model?: string;
        platformVersion?: string;
        uaFullVersion?: string;
    }>;
    readonly mobile?: boolean;
    readonly platform?: string;
}

@Component({
    imports: [Breadcrumbs, InfoHint, IpInsight, NgTemplateOutlet, RouterLink, ToolResult, TuiCardLarge, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle],
    selector: 'app-browser-info',
    styleUrl: './browser-info.css',
    templateUrl: './browser-info.html',
})
export class BrowserInfo implements OnInit {
    private readonly browserInfoApi = inject(BrowserInfoApi);
    private loadSequence = 0;

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Информация о браузере' }];
    protected readonly canvasInfo = canvasFingerprint();
    protected readonly audioInfo = signal<AudioFingerprint | null | undefined>(undefined);
    protected readonly webrtcInfo = signal<WebRtcFingerprint | null | undefined>(undefined);
    protected readonly highEntropy = signal<InfoRow[]>([]);
    protected readonly client = computed(() => [...this.clientBase, ...this.highEntropy()]);
    protected readonly capabilities = capabilityRows();
    protected readonly mediaInfo = signal<MediaInfo | null | undefined>(undefined);
    protected readonly permissionInfo = signal<InfoRow[] | null | undefined>(undefined);
    protected readonly server = signal<BrowserInfoResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly webrtcRows = computed(() => {
        const info = this.webrtcInfo();

        if (!info) {
            return [];
        }

        const values: Array<[string, string | null | undefined, string?, string?, string?, string?]> = [
            ['Локальные адреса', info.local.join(', ') || 'не обнаружены', undefined, 'icecandidate (host)'],
        ];

        if (info.stun.length) {
            info.stun.forEach((ip) => {
                values.push(['Публичный IP через STUN', this.withGeo(ip, ip) ?? ip, this.ipFlag(ip), 'icecandidate (srflx)', ip, this.ipCountry(ip)]);
            });
        } else {
            values.push(['Публичный IP через STUN', 'не обнаружен', undefined, 'icecandidate (srflx)']);
        }

        info.relay.forEach((ip) => {
            values.push(['TURN-релей', this.withGeo(ip, ip) ?? ip, this.ipFlag(ip), 'icecandidate (relay)', ip, this.ipCountry(ip)]);
        });

        return this.rows(values);
    });
    protected readonly webrtcLeak = computed(() => {
        const info = this.webrtcInfo();
        const serverIp = this.server()?.ip?.replace(/^::ffff:/, '');

        if (!serverIp) {
            return null;
        }

        const candidate = info?.stun.map((address) => address.replace(/^::ffff:/, '')).find((address) => address.includes(':') === serverIp.includes(':'));

        if (!candidate) {
            return null;
        }

        const matches = candidate === serverIp;

        return {
            alert: !matches,
            text: matches ? 'Публичный IP WebRTC совпадает с IP, который видит сайт.' : 'Публичный IP WebRTC отличается от IP, который видит сайт.',
        };
    });
    protected readonly connectionRows = computed(() => {
        const server = this.server();
        const ips =
            server?.proxy
                ?.split(',')
                .map((ip) => ip.trim())
                .filter(Boolean) ?? [];
        const hosts = server?.proxyHost?.split(',').map((host) => host.trim()) ?? [];
        const values: Array<[string, string | null | undefined, string?, string?, string?, string?]> = [];

        if (server) {
            values.push([
                'IP-адрес',
                this.withGeo(this.withHost(server.ip, server.ipHost), server.ip),
                this.ipFlag(server.ip),
                'REMOTE_ADDR',
                server.ip ?? undefined,
                this.ipCountry(server.ip),
            ]);
            ips.forEach((ip, index) => {
                values.push([
                    1 === ips.length ? 'Прокси' : `Прокси ${index + 1}`,
                    this.withGeo(this.withHost(ip, hosts[index]), ip),
                    this.ipFlag(ip),
                    'X-Forwarded-For / Client-IP',
                    ip,
                    this.ipCountry(ip),
                ]);
            });
            values.push(
                ['HTTP-протокол', server.httpProtocol, undefined, 'SERVER_PROTOCOL'],
                ['HTTPS', server.https ? 'Да' : 'Нет', undefined, 'HTTPS'],
                ['Порт клиента', server.remotePort, undefined, 'REMOTE_PORT'],
                ['Via', server.via, undefined, 'Via'],
                ['Мобильный номер', server.phoneNumber, undefined, 'WAP-заголовки'],
            );
        }

        return this.rows(values);
    });
    protected readonly geoRows = computed(() => {
        const geo = this.server()?.insight?.geo;

        if (!geo) {
            return [];
        }

        return this.rows([
            ['Континент', this.withCode(geo.continent, geo.continentCode)],
            [
                'Страна',
                this.withCode(geo.country, geo.countryCode),
                geo.countryCode ? countryFlagUrl(geo.countryCode) : undefined,
                undefined,
                undefined,
                geo.country ?? geo.countryCode ?? undefined,
            ],
            ['Регион', geo.region],
            ['Город', geo.city],
            ['Почтовый индекс', geo.postalCode],
            ['Координаты', null != geo.latitude && null != geo.longitude ? `${geo.latitude}, ${geo.longitude}` : null],
            ['Точность геолокации', null != geo.accuracyRadius ? `±${geo.accuracyRadius} км` : null],
            ['Часовой пояс по IP', geo.timezone],
            ['Страна регистрации сети', this.withCode(geo.registeredCountry, geo.registeredCountryCode)],
            ['В Евросоюзе', geo.inEuropeanUnion ? 'Да' : null],
            ['Сеть', geo.network],
        ]);
    });
    protected readonly geoSource = computed(() => {
        const database = this.server()?.geoDatabase;

        if (!database) {
            return 'Источник: MaxMind GeoLite2 City.';
        }

        return `Источник: MaxMind ${database.type.replace(/-/g, ' ')}, сборка ${this.databaseDate(database.buildDate)}.`;
    });
    protected readonly vpn = computed(() => this.server()?.vpn ?? null);
    protected readonly vpnSummary = computed(() => {
        const vpn = this.vpn();

        if (null === vpn) {
            return 'Проверка недоступна.';
        }

        if (!vpn.suspicious) {
            return 'Явных признаков VPN, Tor или прокси не найдено.';
        }

        return 'Обнаружены признаки прокси или VPN.';
    });
    protected readonly rawHeaders = computed(() => {
        const headers = this.server()?.headers ?? {};

        return Object.entries(headers)
            .map(([name, values]) => `${name}: ${values.join(', ')}`)
            .join('\n');
    });
    private readonly clientBase: InfoRow[];

    private static readonly CLIENT_SOURCES: Record<string, string> = {
        'User-Agent': 'navigator.userAgent',
        Платформа: 'navigator.userAgentData.platform ?? navigator.platform',
        Бренды: 'navigator.userAgentData.brands',
        'Мобильное устройство': 'navigator.userAgentData.mobile',
        Производитель: 'navigator.vendor',
        Язык: 'navigator.language',
        Языки: 'navigator.languages',
        Cookies: 'navigator.cookieEnabled',
        Сеть: 'navigator.onLine',
        'Тип сети': 'navigator.connection.effectiveType',
        'Скорость сети': 'navigator.connection.downlink',
        'Задержка сети': 'navigator.connection.rtt',
        'Экономия трафика': 'navigator.connection.saveData',
        'Do Not Track': 'navigator.doNotTrack',
        WebDriver: 'navigator.webdriver',
        Экран: 'screen.width × screen.height',
        'Рабочая область': 'screen.availWidth × screen.availHeight',
        Окно: 'window.innerWidth × window.innerHeight',
        'Плотность пикселей': 'window.devicePixelRatio',
        'Глубина цвета': 'screen.colorDepth',
        Ориентация: 'screen.orientation.type',
        'Логических ядер': 'navigator.hardwareConcurrency',
        Память: 'navigator.deviceMemory',
        'Точек касания': 'navigator.maxTouchPoints',
        'Часовой пояс': 'Intl.DateTimeFormat().resolvedOptions().timeZone',
        'Дата и время': 'new Date().toLocaleString()',
        'Смещение UTC': 'new Date().getTimezoneOffset()',
        Кодировка: 'document.characterSet',
        'WebGL-производитель': 'gl.getParameter(UNMASKED_VENDOR_WEBGL)',
        'WebGL-рендерер': 'gl.getParameter(UNMASKED_RENDERER_WEBGL)',
    };

    constructor() {
        const navigatorWithData = navigator as Navigator & {
            connection?: NetworkConnection;
            deviceMemory?: number;
            userAgentData?: UserAgentData;
        };
        const uaData = navigatorWithData.userAgentData;
        const connection = navigatorWithData.connection;
        const brands = uaData?.brands?.map((item) => `${item.brand} ${item.version}`).join(', ');
        const webgl = this.webglInfo();
        const languages = navigator.languages ?? [];
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const orientation = this.orientation(screen.orientation?.type);
        const now = new Date();

        this.clientBase = this.withSources(
            this.rows([
                ['User-Agent', navigator.userAgent],
                ['Платформа', uaData?.platform ?? navigator.platform],
                ['Бренды', brands],
                ['Мобильное устройство', uaData ? (uaData.mobile ? 'Да' : 'Нет') : null],
                ['Производитель', navigator.vendor],
                ['Язык', navigator.language],
                ['Языки', languages.join(', ')],
                ['Cookies', navigator.cookieEnabled ? 'Включены' : 'Выключены'],
                ['Сеть', navigator.onLine ? 'Онлайн' : 'Офлайн'],
                ['Тип сети', connection?.effectiveType?.toUpperCase()],
                ['Скорость сети', null != connection?.downlink ? `${connection.downlink} Мбит/с` : null],
                ['Задержка сети', connection?.rtt ? `${connection.rtt} мс` : null],
                ['Экономия трафика', connection?.saveData ? 'Включена' : null],
                ['Do Not Track', navigator.doNotTrack === '1' ? 'Включён' : 'Выключен'],
                ['WebDriver', navigator.webdriver ? 'Включён' : null],
                ['Экран', `${screen.width}×${screen.height}`],
                ['Рабочая область', `${screen.availWidth}×${screen.availHeight}`],
                ['Окно', `${window.innerWidth}×${window.innerHeight}`],
                ['Плотность пикселей', `${window.devicePixelRatio}`],
                ['Глубина цвета', `${screen.colorDepth} бит`],
                ['Ориентация', orientation],
                ['Логических ядер', navigator.hardwareConcurrency?.toString()],
                ['Память', navigatorWithData.deviceMemory ? `${navigatorWithData.deviceMemory} ГБ` : null],
                ['Точек касания', navigator.maxTouchPoints?.toString()],
                ['Часовой пояс', timezone],
                ['Дата и время', now.toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'medium' })],
                ['Смещение UTC', utcOffsetNow()],
                ['Кодировка', document.characterSet],
                ['WebGL-производитель', webgl?.vendor],
                ['WebGL-рендерер', webgl?.renderer],
            ]),
            BrowserInfo.CLIENT_SOURCES,
        );

        void this.loadHighEntropy(uaData);
    }

    ngOnInit(): void {
        void this.load();
        void this.loadFingerprints();
        void this.loadMedia();
        void this.loadPermissions();
    }

    private async load(webrtc: readonly string[] = []): Promise<void> {
        const sequence = ++this.loadSequence;
        const initial = !this.server();

        if (initial) {
            this.loading.set(true);
        }

        this.error.set(null);

        try {
            const response = await this.browserInfoApi.load(webrtc);

            if (sequence === this.loadSequence) {
                this.server.set(response);
            }
        } catch (error) {
            if (sequence === this.loadSequence && !this.server()) {
                this.error.set(describeError(error));
            }
        } finally {
            if (sequence === this.loadSequence) {
                this.loading.set(false);
            }
        }
    }

    private async loadFingerprints(): Promise<void> {
        const [audio, webrtc] = await Promise.all([audioFingerprint(), webrtcFingerprint()]);

        this.audioInfo.set(audio);
        this.webrtcInfo.set(webrtc);

        if (webrtc?.stun.length) {
            void this.load(webrtc.stun);
        }
    }

    private async loadMedia(): Promise<void> {
        try {
            this.mediaInfo.set(await mediaSupport());
        } catch {
            this.mediaInfo.set(null);
        }
    }

    private async loadPermissions(): Promise<void> {
        try {
            this.permissionInfo.set(await permissionRows());
        } catch {
            this.permissionInfo.set(null);
        }
    }

    private async loadHighEntropy(uaData?: UserAgentData): Promise<void> {
        if (!uaData?.getHighEntropyValues) {
            return;
        }

        try {
            const values = await uaData.getHighEntropyValues(['architecture', 'bitness', 'model', 'platformVersion', 'uaFullVersion']);

            this.highEntropy.set(
                this.withSources(
                    this.rows([
                        ['Архитектура', values.architecture],
                        ['Разрядность', values.bitness ? `${values.bitness} бит` : null],
                        ['Модель устройства', values.model],
                        ['Версия платформы', values.platformVersion],
                        ['Полная версия браузера', values.uaFullVersion],
                    ]),
                    {
                        Архитектура: 'navigator.userAgentData.getHighEntropyValues()',
                        Разрядность: 'navigator.userAgentData.getHighEntropyValues()',
                        'Модель устройства': 'navigator.userAgentData.getHighEntropyValues()',
                        'Версия платформы': 'navigator.userAgentData.getHighEntropyValues()',
                        'Полная версия браузера': 'navigator.userAgentData.getHighEntropyValues()',
                    },
                ),
            );
        } catch {
            this.highEntropy.set([]);
        }
    }

    private rows(values: Array<[string, string | null | undefined, string?, string?, string?, string?]>): InfoRow[] {
        return values
            .filter((value): value is [string, string, string?, string?, string?, string?] => null !== value[1] && undefined !== value[1] && '' !== value[1])
            .map(([label, value, flag, source, link, flagAlt]) => ({ flag, flagAlt, label, link, source, value }));
    }

    private withSources(rows: InfoRow[], sources: Record<string, string>): InfoRow[] {
        return rows.map((row) => (sources[row.label] ? { ...row, source: sources[row.label] } : row));
    }

    private withHost(value?: string | null, host?: string | null): string | null {
        if (!value) {
            return null;
        }

        return host && host !== value ? `${value} (${host})` : value;
    }

    private withCode(value?: string | null, code?: string | null): string | null {
        if (!value) {
            return null;
        }

        return code ? `${value} (${code})` : value;
    }

    private withGeo(value: string | null, ip?: string | null): string | null {
        if (!value) {
            return null;
        }

        const geo = this.ipGeo(ip);

        return geo ? `${value} — ${this.geoLabel(geo)}` : value;
    }

    private ipGeo(ip?: string | null): BrowserGeoInfo | null {
        const server = this.server();

        if (!ip || !server) {
            return null;
        }

        if (server.ip === ip) {
            return server.insight?.geo ?? null;
        }

        const entry = [...server.proxyGeo, ...server.webrtcGeo].find((item) => item.ip === ip);

        return entry?.geo ?? null;
    }

    private ipFlag(ip?: string | null): string | undefined {
        const code = this.ipGeo(ip)?.countryCode;

        return code ? countryFlagUrl(code) : undefined;
    }

    private ipCountry(ip?: string | null): string | undefined {
        const geo = this.ipGeo(ip);

        return geo?.country ?? geo?.countryCode ?? undefined;
    }

    private geoLabel(geo: BrowserGeoInfo): string {
        const country = geoCountryLabel(geo);
        const place = geo.city ?? geo.region;

        return [country, place].filter(Boolean).join(', ');
    }

    private orientation(type?: string): string | null {
        if (!type) {
            return null;
        }

        if (type.startsWith('portrait')) {
            return 'Портретная';
        }

        if (type.startsWith('landscape')) {
            return 'Альбомная';
        }

        return type;
    }

    private databaseDate(value: string): string {
        return new Date(`${value}T00:00:00`).toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    }

    private webglInfo(): { vendor: string; renderer: string } | null {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') as WebGLRenderingContext | null;

            if (null === gl) {
                return null;
            }

            const extension = gl.getExtension('WEBGL_debug_renderer_info') as {
                UNMASKED_VENDOR_WEBGL: number;
                UNMASKED_RENDERER_WEBGL: number;
            } | null;
            const vendor = extension ? gl.getParameter(extension.UNMASKED_VENDOR_WEBGL) : gl.getParameter(gl.VENDOR);
            const renderer = extension ? gl.getParameter(extension.UNMASKED_RENDERER_WEBGL) : gl.getParameter(gl.RENDERER);

            return {
                vendor: String(vendor),
                renderer: String(renderer),
            };
        } catch {
            return null;
        }
    }
}
