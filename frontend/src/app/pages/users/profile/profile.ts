import { DatePipe } from '@angular/common';
import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TuiButton, TuiCell, TuiIcon, TuiLoader, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';
import { TuiCardLarge } from '@taiga-ui/layout';

import { UserProfileResponse } from '../../../api/generated/models';
import { Auth } from '../../../core/auth';
import { countryFlagUrl } from '../../../core/country-flag';
import { describeError } from '../../../core/problem-details';
import { UsersApi } from '../../../core/users-api';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { CodeHighlightDirective } from '../../../shared/code-highlight/code-highlight.directive';
import { RelativeDatePipe } from '../../../shared/relative-date/relative-date.pipe';
import { UserAvatar } from '../../../shared/user-avatar/user-avatar';

@Component({
    imports: [
        Breadcrumbs,
        CodeHighlightDirective,
        DatePipe,
        RelativeDatePipe,
        RouterLink,
        TuiBadge,
        TuiButton,
        TuiCardLarge,
        TuiCell,
        TuiIcon,
        TuiLoader,
        TuiNotificationDirective,
        TuiTitle,
        UserAvatar,
    ],
    selector: 'app-user-profile',
    styleUrl: './profile.css',
    templateUrl: './profile.html',
})
export class UserProfile implements OnInit {
    private readonly usersApi = inject(UsersApi);
    private readonly route = inject(ActivatedRoute);
    private readonly auth = inject(Auth);
    private readonly destroyRef = inject(DestroyRef);

    protected readonly profile = signal<UserProfileResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly friendBusy = signal(false);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => [
        { label: 'Главная', link: '/' },
        { label: 'Пользователи', link: '/user/users' },
        { label: this.profile()?.username ?? 'Профиль' },
    ]);

    ngOnInit(): void {
        this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            void this.load(params.get('username') ?? this.auth.user()?.username ?? '');
        });
    }

    protected sexLabel(sex: string | null | undefined): string {
        if ('m' === sex) {
            return 'Мужской';
        }

        if ('f' === sex) {
            return 'Женский';
        }

        return 'Не указан';
    }

    protected readonly flagUrl = countryFlagUrl;

    protected async toggleFriend(): Promise<void> {
        const profile = this.profile();

        if (null === profile || this.friendBusy()) {
            return;
        }

        this.friendBusy.set(true);
        this.error.set(null);

        try {
            if (profile.isFriend) {
                await this.usersApi.removeFriend(profile.username);
            } else {
                await this.usersApi.addFriend(profile.username);
            }

            this.profile.set(await this.usersApi.profile(profile.username));
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.friendBusy.set(false);
        }
    }

    private async load(username: string): Promise<void> {
        this.profile.set(null);
        this.error.set(null);

        if ('' === username) {
            this.error.set('Пользователь не найден.');

            return;
        }

        this.loading.set(true);

        try {
            const profile = await this.usersApi.profile(username);

            this.profile.set(profile);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }
}
