import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { UsersApi } from '../../../core/api/users-api';
import { UserEvents } from './events';

describe('UserEvents', () => {
    const events = vi.fn().mockResolvedValue({
        items: [
            {
                id: 1,
                subject: 'Новый файл',
                template: 'friend_file_add',
                createdAt: '2026-01-01T12:30:00+00:00',
                url: '/files/1',
            },
        ],
        total: 1,
        page: 1,
        perPage: 20,
    });
    const queryParams = new BehaviorSubject(convertToParamMap({}));
    const route = { queryParamMap: queryParams.asObservable() };

    beforeEach(async () => {
        queryParams.next(convertToParamMap({}));

        await TestBed.configureTestingModule({
            imports: [UserEvents],
            providers: [{ provide: UsersApi, useValue: { events } }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();
    });

    it('renders the user events', async () => {
        const fixture = TestBed.createComponent(UserEvents);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(events).toHaveBeenCalledWith(1);
        expect(fixture.nativeElement.textContent).toContain('Новый файл');
        expect(fixture.nativeElement.textContent).toContain('01.01.2026');
        expect(fixture.nativeElement.querySelector('a[href="/files/1"]')).not.toBeNull();

        fixture.destroy();
    });
});
