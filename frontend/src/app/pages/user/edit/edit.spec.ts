import { Component, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { AppConfigService } from '../../../core/app-config';
import { Auth } from '../../../core/auth/auth';
import { UsersApi } from '../../../core/api/users-api';
import { UserEdit } from './edit';

@Component({ template: '' })
class Stub {}

describe('UserEdit', () => {
    const profileData = {
        id: 1,
        username: 'alice',
        avatarUrl: 'https://gravatar.com/avatar/1',
        canEdit: true,
        counters: { files: 0, friends: 0, gists: 0 },
        createdAt: '2026-01-01T12:00:00+00:00',
        isFriend: false,
        isOnline: false,
        sex: 'm',
        birthday: '1990-05-06',
        timezone: 'Europe/Minsk',
        country: 'BY',
        countryName: 'Беларусь',
        vk: 'id1',
        info: 'О себе',
    };
    const profile = vi.fn().mockResolvedValue(profileData);
    const updateProfile = vi.fn().mockResolvedValue(profileData);
    const auth = { user: () => ({ username: 'alice' }) };
    const appConfig = {
        config: signal(null),
        load: vi.fn().mockResolvedValue({
            captcha: { enabled: false, siteKey: '' },
            countries: [
                { code: 'BY', name: 'Беларусь' },
                { code: 'RU', name: 'Россия' },
            ],
            timezones: [
                { id: 'Europe/Minsk', name: 'Минск' },
                { id: 'Europe/Moscow', name: 'Москва' },
            ],
        }),
    };

    beforeEach(async () => {
        profile.mockClear();
        updateProfile.mockClear();
        appConfig.load.mockClear();

        await TestBed.configureTestingModule({
            imports: [UserEdit],
            providers: [
                { provide: UsersApi, useValue: { profile, updateProfile } },
                { provide: AppConfigService, useValue: appConfig },
                { provide: Auth, useValue: auth },
                provideRouter([{ path: 'user/profile/:username', component: Stub }]),
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('fills the form from the profile', async () => {
        const fixture = TestBed.createComponent(UserEdit);

        fixture.detectChanges();
        await new Promise((resolve) => setTimeout(resolve));
        await fixture.whenStable();
        fixture.detectChanges();

        expect(profile).toHaveBeenCalledWith('alice');
        expect(appConfig.load).toHaveBeenCalled();

        const text = fixture.nativeElement.textContent as string;

        expect(text).toContain('Дата рождения');
        expect((fixture.nativeElement.querySelector('input[name="birthday"]') as HTMLInputElement).value).toBe('1990-05-06');
        expect((fixture.nativeElement.querySelector('input[name="sex"]') as HTMLInputElement).value).toBe('Мужской');
        expect((fixture.nativeElement.querySelector('input[name="country"]') as HTMLInputElement).value).toBe('Беларусь');
        expect((fixture.nativeElement.querySelector('input[name="timezone"]') as HTMLInputElement).value).toBe('Минск');
        expect((fixture.nativeElement.querySelector('input[name="vk"]') as HTMLInputElement).value).toBe('id1');
        expect((fixture.nativeElement.querySelector('textarea[name="info"]') as HTMLTextAreaElement).value).toBe('О себе');

        fixture.destroy();
    });

    it('submits the profile changes', async () => {
        const fixture = TestBed.createComponent(UserEdit);

        fixture.detectChanges();
        await new Promise((resolve) => setTimeout(resolve));
        await fixture.whenStable();
        fixture.detectChanges();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('submit'));
        await fixture.whenStable();

        expect(updateProfile).toHaveBeenCalledWith({
            sex: 'm',
            birthday: '1990-05-06',
            timezone: 'Europe/Minsk',
            country: 'BY',
            vk: 'id1',
            info: 'О себе',
        });

        fixture.destroy();
    });
});
