import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { TuiButton, TuiInput, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { Auth } from '../../core/auth/auth';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';

@Component({
    imports: [Breadcrumbs, FormsModule, TuiButton, TuiCardLarge, TuiInput, TuiNotificationDirective],
    selector: 'app-login',
    styleUrl: './login.css',
    templateUrl: './login.html',
})
export class Login implements OnInit {
    private readonly auth = inject(Auth);
    private readonly router = inject(Router);
    private readonly route = inject(ActivatedRoute);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Вход' }];
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly registered = signal<string | null>(null);

    protected username = '';
    protected password = '';

    private returnUrl = '/files';

    ngOnInit(): void {
        this.returnUrl = this.route.snapshot.queryParamMap.get('returnUrl') ?? '/files';

        const registered = this.route.snapshot.queryParamMap.get('registered');

        if (registered) {
            this.username = registered;
            this.registered.set(registered);
        }

        if (this.auth.authenticated()) {
            void this.router.navigateByUrl(this.returnUrl);
        }
    }

    protected async submit(): Promise<void> {
        this.error.set(null);
        this.loading.set(true);

        try {
            await this.auth.login(this.username, this.password);
            await this.router.navigateByUrl(this.returnUrl);
        } catch {
            this.error.set('Неверный логин или пароль.');
        } finally {
            this.loading.set(false);
        }
    }
}
