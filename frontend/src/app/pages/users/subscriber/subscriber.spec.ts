import { Component } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { UsersApi } from '../../../core/users-api';
import { UserSubscriber } from './subscriber';

@Component({ template: '' })
class Stub {}

describe('UserSubscriber', () => {
    const subscriber = vi.fn().mockResolvedValue({ emailNews: true, emailFriends: false });
    const updateSubscriber = vi.fn().mockResolvedValue({ emailNews: true, emailFriends: false });

    beforeEach(async () => {
        subscriber.mockClear();
        updateSubscriber.mockClear();

        await TestBed.configureTestingModule({
            imports: [UserSubscriber],
            providers: [
                { provide: UsersApi, useValue: { subscriber, updateSubscriber } },
                provideRouter([{ path: 'user/profile/:username', component: Stub }]),
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('loads the current subscription settings', async () => {
        const fixture = TestBed.createComponent(UserSubscriber);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(subscriber).toHaveBeenCalled();

        const inputs = fixture.nativeElement.querySelectorAll('input') as NodeListOf<HTMLInputElement>;

        expect(inputs[0].checked).toBe(true);
        expect(inputs[1].checked).toBe(false);

        fixture.destroy();
    });

    it('saves the subscription settings', async () => {
        const fixture = TestBed.createComponent(UserSubscriber);

        await fixture.whenStable();
        fixture.detectChanges();

        const inputs = fixture.nativeElement.querySelectorAll('input') as NodeListOf<HTMLInputElement>;

        inputs[1].click();
        await fixture.whenStable();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('submit'));
        await fixture.whenStable();
        fixture.detectChanges();

        expect(updateSubscriber).toHaveBeenCalledWith({ emailNews: true, emailFriends: true });
        expect(fixture.nativeElement.textContent).toContain('Настройки сохранены.');

        fixture.destroy();
    });
});
