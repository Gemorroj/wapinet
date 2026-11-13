import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { TuiButton, TuiCheckbox, TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { describeError } from '../../../core/problem-details';
import { UsersApi } from '../../../core/users-api';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';

@Component({
    imports: [Breadcrumbs, FormsModule, RouterLink, TuiButton, TuiCardLarge, TuiCheckbox, TuiLoader, TuiNotificationDirective],
    selector: 'app-user-subscriber',
    styleUrl: './subscriber.css',
    templateUrl: './subscriber.html',
})
export class UserSubscriber implements OnInit {
    private readonly usersApi = inject(UsersApi);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Профиль', link: '/user/profile' }, { label: 'Подписки' }];
    protected readonly loading = signal(false);
    protected readonly saving = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly saved = signal(false);
    protected readonly emailNews = signal(false);
    protected readonly emailFriends = signal(false);

    async ngOnInit(): Promise<void> {
        this.loading.set(true);

        try {
            const subscriber = await this.usersApi.subscriber();

            this.emailNews.set(subscriber.emailNews);
            this.emailFriends.set(subscriber.emailFriends);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected async submit(): Promise<void> {
        this.error.set(null);
        this.saved.set(false);
        this.saving.set(true);

        try {
            const subscriber = await this.usersApi.updateSubscriber({
                emailNews: this.emailNews(),
                emailFriends: this.emailFriends(),
            });

            this.emailNews.set(subscriber.emailNews);
            this.emailFriends.set(subscriber.emailFriends);
            this.saved.set(true);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.saving.set(false);
        }
    }
}
