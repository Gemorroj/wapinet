import { Component, DestroyRef, OnInit, ViewChild, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiLoader, TuiNotificationDirective } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { GuestbookMessageResponse, IpInsightResponse } from '../../api/generated/models';
import { AppConfigService } from '../../core/app-config';
import { Auth } from '../../core/auth';
import { countryFlagUrl } from '../../core/country-flag';
import { GuestbookApi } from '../../core/guestbook-api';
import { describeError } from '../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { BbcodeEditor } from '../../shared/bbcode-editor/bbcode-editor';
import { Captcha } from '../../shared/captcha/captcha';
import { CodeHighlightDirective } from '../../shared/code-highlight/code-highlight.directive';
import { IpBadges } from '../../shared/ip-badges/ip-badges';
import { RelativeDatePipe } from '../../shared/relative-date/relative-date.pipe';
import { UserAvatar } from '../../shared/user-avatar/user-avatar';
import { Pagination } from '../../shared/pagination/pagination';
import { readPage } from '../../core/page-query';

@Component({
    imports: [
        Pagination,
        Breadcrumbs,
        BbcodeEditor,
        Captcha,
        CodeHighlightDirective,
        FormsModule,
        IpBadges,
        RelativeDatePipe,
        RouterLink,
        TuiButton,
        TuiCardLarge,
        TuiLoader,
        TuiNotificationDirective,
        UserAvatar,
    ],
    selector: 'app-guestbook',
    styleUrl: './guestbook.css',
    templateUrl: './guestbook.html',
})
export class Guestbook implements OnInit {
    protected readonly auth = inject(Auth);
    protected readonly appConfig = inject(AppConfigService);
    private readonly guestbookApi = inject(GuestbookApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    @ViewChild(Captcha) private captcha?: Captcha;
    @ViewChild(BbcodeEditor) private editor?: BbcodeEditor;

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Гостевая' }];
    protected readonly flagUrl = countryFlagUrl;
    protected readonly items = signal<GuestbookMessageResponse[]>([]);
    protected readonly total = signal(0);
    protected readonly page = signal(1);
    protected readonly perPage = signal(20);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly notice = signal<string | null>(null);
    protected readonly sending = signal(false);
    protected readonly message = signal('');
    protected readonly captchaActive = signal(false);
    protected readonly captchaToken = signal('');
    protected readonly bCheck = signal('');
    protected readonly openAnonymous = signal<number | null>(null);
    protected readonly insights = signal<Record<number, IpInsightResponse | null>>({});
    protected readonly loadingInsight = signal<number | null>(null);

    async ngOnInit(): Promise<void> {
        this.route.queryParamMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            this.page.set(readPage(params));
            void this.load();
        });

        try {
            await this.appConfig.load();
        } catch {
            if (!this.auth.authenticated()) {
                this.error.set('Не удалось загрузить настройки капчи.');
            }
        }
    }

    protected async submit(): Promise<void> {
        const message = this.message().trim();

        if ('' === message) {
            this.error.set('Введите сообщение.');
            return;
        }

        const isAnonymous = !this.auth.authenticated();
        const config = this.appConfig.config();

        if (isAnonymous && config?.captcha?.enabled) {
            if (!this.captchaActive()) {
                this.captchaActive.set(true);
                this.error.set('Подтвердите, что вы не робот.');

                return;
            }

            if ('' === this.captchaToken()) {
                this.error.set('Подтвердите, что вы не робот.');

                return;
            }
        }

        this.sending.set(true);
        this.error.set(null);
        this.notice.set(null);

        try {
            await this.guestbookApi.create({
                message,
                captchaToken: isAnonymous ? this.captchaToken() || null : null,
                bCheck: this.bCheck() || null,
            });

            this.message.set('');
            this.captchaToken.set('');
            this.bCheck.set('');
            this.captcha?.reset();
            this.notice.set('Сообщение успешно добавлено.');

            if (1 === this.page()) {
                await this.load();
            } else {
                await this.router.navigate([], { queryParams: { page: null }, queryParamsHandling: 'merge' });
            }
        } catch (error) {
            if (isAnonymous && config?.captcha?.enabled) {
                this.captchaToken.set('');
                this.captcha?.reset();
            }

            this.error.set(describeError(error));
        } finally {
            this.sending.set(false);
        }
    }

    protected reply(item: GuestbookMessageResponse): void {
        const author = item.user?.username ?? 'Аноним';
        const text = item.message.replace(/\[\/?quote[^\]]*\]/gi, '').trim();
        const quoted = text.length > 500 ? `${text.slice(0, 500)}…` : text;

        this.message.update((current) => `[quote=${author}]${quoted}[/quote]\n\n${current}`);
        this.editor?.focus();
    }

    protected async goToPage(page: number): Promise<void> {
        await this.router.navigate([], { queryParams: { page }, queryParamsHandling: 'merge' });
    }

    protected toggleAnonymous(id: number): void {
        const opening = this.openAnonymous() !== id;

        this.openAnonymous.set(opening ? id : null);

        if (opening && !(id in this.insights())) {
            void this.loadInsight(id);
        }
    }

    protected onCaptchaFailed(): void {
        this.error.set('Не удалось загрузить капчу. Обновите страницу.');
    }

    private async load(): Promise<void> {
        this.loading.set(true);
        this.error.set(null);
        this.openAnonymous.set(null);

        try {
            const result = await this.guestbookApi.list(this.page());

            this.items.set(result.items);
            this.total.set(result.total);
            this.perPage.set(result.perPage);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    private async loadInsight(id: number): Promise<void> {
        this.loadingInsight.set(id);

        try {
            const insight = await this.guestbookApi.insight(id);

            this.insights.update((current) => ({ ...current, [id]: insight }));
        } catch {
            this.insights.update((current) => ({ ...current, [id]: null }));
        } finally {
            if (this.loadingInsight() === id) {
                this.loadingInsight.set(null);
            }
        }
    }
}
