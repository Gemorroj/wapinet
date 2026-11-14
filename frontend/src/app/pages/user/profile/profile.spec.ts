import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';
import { BehaviorSubject } from 'rxjs';

import { UserProfileResponse } from '../../../api/generated/models';
import { Auth } from '../../../core/auth/auth';
import { UsersApi } from '../../../core/api/users-api';
import { UserProfile } from './profile';

describe('UserProfile', () => {
    const profileData: UserProfileResponse = {
        id: 1,
        username: 'alice',
        avatarUrl: 'https://gravatar.com/avatar/1',
        canEdit: true,
        counters: { files: 3, friends: 2, gists: 1 },
        createdAt: '2026-01-01T12:00:00+00:00',
        isFriend: false,
        isOnline: true,
        sex: 'm',
        birthday: '1990-05-06',
        country: 'RU',
        countryName: 'Россия',
        lastActivity: '2026-02-01T12:00:00+00:00',
        info: 'О себе',
        infoHtml: '<p>О себе</p>',
        vk: 'id1',
        vkProfile: { id: 1, name: 'Павел', photoUrl: 'https://vk.example/photo.jpg', isOnline: true },
    };

    const profile = vi.fn().mockResolvedValue(profileData);
    const addFriend = vi.fn().mockResolvedValue(undefined);
    const removeFriend = vi.fn().mockResolvedValue(undefined);
    const auth = { user: () => ({ username: 'alice' }) };
    const params = new BehaviorSubject(convertToParamMap({ username: 'alice' }));
    const route = { paramMap: params.asObservable() };

    beforeEach(async () => {
        params.next(convertToParamMap({ username: 'alice' }));
        profile.mockClear();
        profile.mockResolvedValue(profileData);

        await TestBed.configureTestingModule({
            imports: [UserProfile],
            providers: [
                { provide: UsersApi, useValue: { profile, addFriend, removeFriend } },
                { provide: Auth, useValue: auth },
                provideRouter([]),
                { provide: ActivatedRoute, useValue: route },
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('renders the profile details', async () => {
        const fixture = TestBed.createComponent(UserProfile);

        await fixture.whenStable();
        fixture.detectChanges();

        const text = fixture.nativeElement.textContent as string;

        expect(profile).toHaveBeenCalledWith('alice');
        expect(text).toContain('alice');
        expect(text).toContain('Мужской');
        expect(text).toContain('06.05.1990');
        expect(text).toContain('Россия');
        expect(text).toContain('Файлы');
        expect(text).toContain('Блоги');
        expect(text).toContain('Друзья');
        expect(
            Array.from(fixture.nativeElement.querySelectorAll('.app-list [tuiBadge]') as NodeListOf<HTMLElement>)
                .map((badge) => badge.textContent?.trim())
                .filter((text): text is string => !!text),
        ).toEqual(['3', '1', '2']);
        expect(text).toContain('ВКонтакте');
        expect(text).toContain('Павел');
        expect((fixture.nativeElement.querySelector('.profile__vk-link img') as HTMLImageElement).src).toContain('https://vk.example/photo.jpg');
        expect(text).toContain('Подписки');
        expect(text).toContain('Редактировать');
        expect(text).not.toContain('онлайн');
        expect(fixture.nativeElement.querySelectorAll('.online-badge').length).toBe(2);

        fixture.destroy();
    });

    it('adds a friend from another profile', async () => {
        profile.mockResolvedValueOnce({ ...profileData, canEdit: false, isFriend: false });

        const fixture = TestBed.createComponent(UserProfile);

        await fixture.whenStable();
        fixture.detectChanges();

        const button = Array.from(fixture.nativeElement.querySelectorAll('button') as NodeListOf<HTMLButtonElement>).find((item) =>
            item.textContent?.includes('Добавить в друзья'),
        );

        button?.click();
        await fixture.whenStable();

        expect(addFriend).toHaveBeenCalledWith('alice');

        fixture.destroy();
    });
});
