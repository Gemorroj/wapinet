import { Component, OnInit, ViewChild, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { TuiButton, TuiInput, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { AppConfigService } from '../../core/app-config';
import { describeError } from '../../core/problem-details';
import { UsersApi } from '../../core/users-api';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { Captcha } from '../../shared/captcha/captcha';

@Component({
    imports: [Breadcrumbs, Captcha, FormsModule, RouterLink, TuiButton, TuiCardLarge, TuiInput, TuiNotificationDirective],
    selector: 'app-registration',
    styleUrl: './registration.css',
    templateUrl: './registration.html',
})
export class Registration implements OnInit {
    private readonly usersApi = inject(UsersApi);
    protected readonly appConfig = inject(AppConfigService);
    private readonly router = inject(Router);

    @ViewChild(Captcha) private captcha?: Captcha;

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Регистрация' }];
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly captchaActive = signal(false);
    protected readonly captchaToken = signal<string | null>(null);
    protected username = '';
    protected email = '';
    protected password = '';
    protected passwordRepeat = '';

    async ngOnInit(): Promise<void> {
        try {
            await this.appConfig.load();
        } catch {
            // The captcha is optional; registration still works without the config.
        }
    }

    protected async submit(): Promise<void> {
        this.error.set(null);

        if (this.password !== this.passwordRepeat) {
            this.error.set('Пароли не совпадают.');

            return;
        }

        if (this.appConfig.config()?.captcha?.enabled) {
            if (!this.captchaActive()) {
                this.captchaActive.set(true);
                this.error.set('Подтвердите, что вы не робот.');

                return;
            }

            if (!this.captchaToken()) {
                this.error.set('Подтвердите, что вы не робот.');

                return;
            }
        }

        this.loading.set(true);

        try {
            await this.usersApi.register({
                username: this.username,
                email: this.email,
                password: this.password,
                captchaToken: this.captchaToken(),
            });

            await this.router.navigate(['/login'], { queryParams: { registered: this.username } });
        } catch (error) {
            if (this.appConfig.config()?.captcha?.enabled) {
                this.captchaToken.set(null);
                this.captcha?.reset();
            }

            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected onCaptchaFailed(): void {
        this.error.set('Не удалось загрузить капчу. Обновите страницу.');
    }
}
