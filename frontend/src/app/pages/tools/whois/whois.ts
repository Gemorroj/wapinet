import { DatePipe } from '@angular/common';
import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiTitle } from '@taiga-ui/core';
import { TuiChip } from '@taiga-ui/kit/components/chip';
import { TuiCardLarge } from '@taiga-ui/layout';

import { BrowserGeoInfo, WhoisDnsRecord, WhoisResponse } from '../../../api/generated/models';
import { countryFlagUrl, geoCountryLabel } from '../../../core/country-flag';
import { describeError } from '../../../core/problem-details';
import { WhoisApi } from '../../../core/whois-api';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { CodeBlock } from '../../../shared/code-block/code-block';
import { InfoHint } from '../../../shared/info-hint/info-hint';
import { IpInsight } from '../../../shared/ip-insight/ip-insight';
import { ToolResult } from '../../../shared/tool-result/tool-result';

type WhoisProtocol = 'auto' | 'rdap' | 'whois';

interface WhoisExpiry {
    readonly label: string;
    readonly eventLabel: string;
    readonly date: string;
    readonly tone: string;
}

@Component({
    imports: [
        Breadcrumbs,
        CodeBlock,
        DatePipe,
        FormsModule,
        InfoHint,
        IpInsight,
        RouterLink,
        TuiButton,
        TuiCardLarge,
        TuiChip,
        TuiInput,
        TuiLoader,
        TuiNotificationDirective,
        TuiTextfield,
        TuiTitle,
        ToolResult,
    ],
    selector: 'app-whois',
    styleUrl: './whois.css',
    templateUrl: './whois.html',
})
export class Whois implements OnInit {
    private readonly whoisApi = inject(WhoisApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);
    private loadSequence = 0;

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'WHOIS / RDAP' }];
    protected readonly query = signal('');
    protected readonly protocol = signal<WhoisProtocol>('auto');
    protected readonly result = signal<WhoisResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly kindLabel = computed(() => this.kind(this.result()?.kind ?? ''));
    protected readonly protocolLabel = computed(() => {
        const protocol = this.result()?.protocol;

        return 'whois' === protocol ? 'WHOIS' : 'RDAP';
    });
    protected readonly expiry = computed<WhoisExpiry | null>(() => {
        const events = this.result()?.events ?? [];
        const labels = ['Истекает', 'Истекает у регистратора', 'Оплачен до'];

        for (const label of labels) {
            const event = events.find((item) => item.action === label);

            if (!event) {
                continue;
            }

            const time = Date.parse(event.date);

            if (Number.isNaN(time)) {
                continue;
            }

            const days = Math.ceil((time - Date.now()) / 86_400_000);

            return {
                label: this.daysLabel(days),
                eventLabel: event.action,
                date: event.date,
                tone: days < 0 ? 'whois__expiry_bad' : days <= 30 ? 'whois__expiry_warn' : 'whois__expiry_ok',
            };
        }

        return null;
    });

    ngOnInit(): void {
        this.route.queryParamMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            const query = params.get('query')?.trim() ?? '';
            const value = params.get('protocol');
            const protocol = ['auto', 'rdap', 'whois'].includes(value ?? '') ? (value as WhoisProtocol) : 'auto';

            if (query === this.query().trim() && protocol === this.protocol()) {
                return;
            }

            this.query.set(query);
            this.protocol.set(protocol);

            if (query) {
                void this.lookup();
            }
        });
    }

    protected async lookup(): Promise<void> {
        const query = this.query().trim();
        const sequence = ++this.loadSequence;

        this.loading.set(true);
        this.error.set(null);
        this.updateUrl(query);

        try {
            const result = await this.whoisApi.lookup(query, this.protocol());

            if (sequence !== this.loadSequence) {
                return;
            }

            this.result.set(result);

            if ('' === query) {
                this.query.set(result.query);
                this.updateUrl(result.query);
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

    protected myIp(): void {
        this.query.set('');
        void this.lookup();
    }

    protected roleLabel(role: string): string {
        return (
            {
                registrar: 'Регистратор',
                registrant: 'Владелец',
                administrative: 'Администратор',
                technical: 'Технический контакт',
                abuse: 'Жалобы',
                billing: 'Биллинг',
            }[role] ?? role
        );
    }

    protected detailFlag(field: { label: string; value: string }): string | undefined {
        if ('Страна' !== field.label) {
            return undefined;
        }

        const code = field.value.trim().toUpperCase();

        return /^[A-Z]{2}$/.test(code) ? countryFlagUrl(code) : undefined;
    }

    protected readonly countryLabel = geoCountryLabel;

    protected flag(geo?: BrowserGeoInfo | null): string | undefined {
        const code = geo?.countryCode;

        return code ? countryFlagUrl(code) : undefined;
    }

    protected fieldHint(label: string): string | null {
        return (
            {
                Handle: 'Идентификатор объекта в реестре.',
                DNSSEC: 'Подписаны ли DNS-ответы домена криптографически.',
                CIDR: 'Сеть в нотации CIDR.',
                ASN: 'Номер автономной системы.',
                'Родительская сеть': 'Родительский блок адресов в иерархии реестра.',
                Тип: 'Тип выделения адресов в реестре.',
            }[label] ?? null
        );
    }

    protected recordHost(record: WhoisDnsRecord): string {
        if ('MX' !== record.type) {
            return record.value;
        }

        const parts = record.value.split(/\s+/);

        return parts.length > 1 ? parts.slice(1).join(' ') : record.value;
    }

    protected recordPrefix(record: WhoisDnsRecord): string | null {
        if ('MX' !== record.type) {
            return null;
        }

        const parts = record.value.split(/\s+/);

        return parts.length > 1 ? parts[0] : null;
    }

    protected recordTitle(type: string): string {
        return (
            {
                A: 'A — IPv4-адрес',
                AAAA: 'AAAA — IPv6-адрес',
                CNAME: 'CNAME — каноническое имя',
                MX: 'MX — почтовый сервер',
                NS: 'NS — DNS-сервер',
                PTR: 'PTR — обратная DNS-запись',
            }[type] ?? type
        );
    }

    protected statusTone(status: string): string {
        const value = status.toLowerCase().replace(/[\s_-]+/g, '');

        if (/(hold|redemption|pendingdelete)/.test(value)) {
            return 'whois__status_bad';
        }

        if (/^(ok|active)$/.test(value) || value.endsWith('prohibited')) {
            return 'whois__status_ok';
        }

        if (/(pending|inactive|suspended)/.test(value)) {
            return 'whois__status_warn';
        }

        return '';
    }

    protected statusTitle(status: string): string | null {
        return (
            {
                ok: 'Нет ограничивающих статусов',
                active: 'Домен активен',
                clienttransferprohibited: 'Запрет передачи домена другому регистратору',
                servertransferprohibited: 'Передача запрещена реестром',
                clientdeleteprohibited: 'Запрет удаления домена',
                serverdeleteprohibited: 'Удаление запрещено реестром',
                clientupdateprohibited: 'Запрет изменения домена',
                serverupdateprohibited: 'Изменение запрещено реестром',
                clienthold: 'Домен приостановлен регистратором',
                serverhold: 'Домен приостановлен реестром',
                pendingdelete: 'Домен ожидает удаления',
                pendingtransfer: 'Ожидает передачи другому регистратору',
                redemptionperiod: 'Период восстановления после удаления',
                inactive: 'Домен не делегирован или не настроен',
            }[status.toLowerCase().replace(/[\s_-]+/g, '')] ?? null
        );
    }

    private daysLabel(days: number): string {
        if (days < 0) {
            return `Просрочен на ${Math.abs(days)} ${this.plural(Math.abs(days))}`;
        }

        if (0 === days) {
            return 'Истекает сегодня';
        }

        if (1 === days) {
            return 'Остался 1 день';
        }

        return `Осталось ${days} ${this.plural(days)}`;
    }

    private plural(count: number): string {
        const mod10 = count % 10;
        const mod100 = count % 100;

        if (1 === mod10 && 11 !== mod100) {
            return 'день';
        }

        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
            return 'дня';
        }

        return 'дней';
    }

    private updateUrl(query: string): void {
        void this.router.navigate([], {
            queryParams: {
                query: '' === query ? null : query,
                protocol: this.protocol(),
            },
            replaceUrl: true,
        });
    }

    private kind(kind: string): string {
        return (
            {
                domain: 'Домен',
                ipv4: 'IPv4',
                ipv6: 'IPv6',
                cidr: 'CIDR',
                asn: 'ASN',
            }[kind] ?? kind
        );
    }
}
