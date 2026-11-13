import { Component, computed, input } from '@angular/core';
import { TuiTitle } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { IpInsightResponse } from '../../api/generated/models';
import { countryFlagUrl, geoCountryLabel } from '../../core/country-flag';
import { InfoHint } from '../info-hint/info-hint';
import { IpBadges } from '../ip-badges/ip-badges';

interface InsightRow {
    readonly flag?: string;
    readonly flagAlt?: string;
    readonly hint?: string;
    readonly label: string;
    readonly value: string;
}

@Component({
    imports: [InfoHint, IpBadges, TuiCardLarge, TuiTitle],
    selector: 'app-ip-insight',
    styleUrl: './ip-insight.css',
    templateUrl: './ip-insight.html',
})
export class IpInsight {
    readonly insight = input.required<IpInsightResponse>();
    readonly showGeo = input(true);
    readonly showPtr = input(true);
    protected readonly rows = computed(() => {
        const insight = this.insight();
        const geo = insight.geo;
        const rows: InsightRow[] = [{ label: 'IP', value: insight.ip }];

        if (this.showGeo()) {
            const country = geoCountryLabel(geo);

            if (country) {
                rows.push({
                    flag: geo?.countryCode ? countryFlagUrl(geo.countryCode) : undefined,
                    flagAlt: country,
                    label: 'Страна',
                    value: country,
                });
            }

            this.push(rows, 'Регион', geo?.region);
            this.push(rows, 'Город', geo?.city);
        }

        const kind = this.kindLabel(insight.kind);

        if (null !== kind) {
            rows.push({
                hint: 'Категория ASN в PeeringDB. Её указывает сам оператор сети, поэтому тип может быть неточным.',
                label: 'Тип сети',
                value: kind,
            });
        }

        this.push(rows, 'Оператор', insight.organization);
        this.push(rows, 'ASN', insight.asn?.toString());
        this.push(rows, 'Сеть', insight.network);
        if (this.showPtr()) {
            this.push(
                rows,
                'PTR',
                insight.ptr,
                insight.dnsProvider ? `Обратная DNS-запись, запрошена через ${insight.dnsProvider} DNS-over-HTTPS.` : undefined,
            );
        }

        return rows;
    });

    private kindLabel(kind?: string | null): string | null {
        return 'hosting' === kind ? 'Хостинг' : 'isp' === kind ? 'Провайдер' : null;
    }

    private push(rows: InsightRow[], label: string, value?: string | null, hint?: string): void {
        if (value) {
            rows.push({ hint, label, value });
        }
    }
}
