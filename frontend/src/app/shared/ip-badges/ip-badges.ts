import { Component, computed, input } from '@angular/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';

import { IpInsightResponse } from '../../api/generated/models';

interface InsightSignal {
    readonly appearance: string;
    readonly label: string;
}

@Component({
    imports: [TuiBadge],
    selector: 'app-ip-badges',
    styleUrl: './ip-badges.css',
    templateUrl: './ip-badges.html',
})
export class IpBadges {
    readonly insight = input.required<IpInsightResponse>();
    readonly colored = input(false);
    readonly showKind = input(true);
    protected readonly kindLabel = computed(() => {
        const kind = this.insight().kind;

        return 'hosting' === kind ? 'Хостинг' : 'isp' === kind ? 'Провайдер' : null;
    });
    protected readonly kindAppearance = computed(() => (this.colored() && 'hosting' === this.insight().kind ? 'warning' : 'neutral'));
    protected readonly signals = computed<InsightSignal[]>(() => {
        const reputation = this.insight().reputation;
        const signals: InsightSignal[] = [];

        if (reputation?.torExit) {
            signals.push({ appearance: 'negative', label: 'Tor' });
        }

        if (reputation?.appears) {
            signals.push({ appearance: 'negative', label: 'Спам' });
        }

        return signals;
    });
}
