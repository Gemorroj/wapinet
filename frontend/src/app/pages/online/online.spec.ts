import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { OnlineApi } from '../../core/online-api';
import { Online } from './online';

describe('Online', () => {
    const list = vi.fn().mockResolvedValue({
        items: [
            {
                id: 1,
                datetime: '2026-01-01T12:00:00+00:00',
                browser: 'Firefox',
                ip: null,
                path: null,
            },
            {
                id: 2,
                datetime: '2026-01-01T12:01:00+00:00',
                browser: 'Chrome',
                ip: '127.0.0.1',
                path: '/files',
            },
        ],
        count: 2,
    });

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Online],
            providers: [{ provide: OnlineApi, useValue: { list } }, provideRouter([]), provideTaiga()],
        }).compileComponents();
    });

    it('renders online sessions', async () => {
        const fixture = TestBed.createComponent(Online);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Всего: 2');
        expect(text).toContain('Firefox');
        expect(text).toContain('Chrome');
        expect(text).toContain('IP: 127.0.0.1');
        expect(text).toContain('/files');
        expect(text).toContain('Обновлено:');

        fixture.destroy();
    });
});
