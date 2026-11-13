import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { IpInsightResponse } from '../../api/generated/models';
import { IpBadges } from './ip-badges';

describe('IpBadges', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [IpBadges],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders network type and reputation signals', () => {
        const fixture = TestBed.createComponent(IpBadges);

        fixture.componentRef.setInput('insight', {
            ip: '1.2.3.4',
            kind: 'hosting',
            reputation: { appears: true, torExit: true },
        } as IpInsightResponse);
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Хостинг');
        expect(text).toContain('Tor');
        expect(text).toContain('Спам');
        expect(fixture.nativeElement.querySelectorAll('[tuiBadge]').length).toBe(3);
    });

    it('hides the network type when requested but keeps signals', () => {
        const fixture = TestBed.createComponent(IpBadges);

        fixture.componentRef.setInput('insight', {
            ip: '1.2.3.4',
            kind: 'hosting',
            reputation: { appears: true, torExit: true },
        } as IpInsightResponse);
        fixture.componentRef.setInput('showKind', false);
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).not.toContain('Хостинг');
        expect(text).toContain('Tor');
        expect(text).toContain('Спам');
        expect(fixture.nativeElement.querySelectorAll('[tuiBadge]').length).toBe(2);
    });

    it('renders nothing without kind and signals', () => {
        const fixture = TestBed.createComponent(IpBadges);

        fixture.componentRef.setInput('insight', { ip: '1.2.3.4' } as IpInsightResponse);
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelectorAll('[tuiBadge]').length).toBe(0);
    });
});
