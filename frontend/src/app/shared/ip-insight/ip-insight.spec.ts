import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { IpInsightResponse } from '../../api/generated/models';
import { IpInsight } from './ip-insight';

describe('IpInsight', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [IpInsight],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders provider and reputation signals', () => {
        const fixture = TestBed.createComponent(IpInsight);
        const insight = {
            ip: '3.5.140.1',
            kind: 'hosting',
            organization: 'AMAZON-02 - Amazon.com, Inc., US',
            asn: 16509,
            network: '3.5.140.0/22',
            ptr: 'ec2-3-5-140-1.example',
            dnsProvider: 'Cloudflare',
            geo: { country: 'США', countryCode: 'US', region: 'Вирджиния', city: 'Ашберн' },
            reputation: { appears: false, torExit: true },
        } as IpInsightResponse;

        fixture.componentRef.setInput('insight', insight);
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Хостинг');
        expect(text).toContain('Tor');
        expect(text).toContain('AMAZON-02');
        expect(text).toContain('16509');
        expect(text).toContain('3.5.140.0/22');
        expect(text).toContain('ec2-3-5-140-1.example');
        expect(text).toContain('Тип сети');
        expect(text).toContain('Оператор');
        expect(fixture.nativeElement.querySelectorAll('app-ip-badges [tuiBadge]').length).toBe(1);
        expect(fixture.nativeElement.querySelectorAll('app-info-hint').length).toBe(2);
    });
});
