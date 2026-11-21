import { AfterViewInit, Component, ElementRef, OnDestroy, ViewChild, inject, input, output } from '@angular/core';

import { YandexCaptcha } from '../../core/auth/yandex-captcha';

@Component({
    selector: 'app-captcha',
    styleUrl: './captcha.css',
    template: '<div #container class="captcha"></div>',
})
export class Captcha implements AfterViewInit, OnDestroy {
    readonly siteKey = input.required<string>();
    readonly token = output<string>();
    readonly failed = output<void>();

    @ViewChild('container', { static: true }) private container!: ElementRef<HTMLElement>;

    private readonly captcha = inject(YandexCaptcha);
    private widgetId: number | null = null;
    private destroyed = false;

    async ngAfterViewInit(): Promise<void> {
        try {
            const widgetId = await this.captcha.render(this.container.nativeElement, this.siteKey(), (token) => this.token.emit(token));

            if (this.destroyed) {
                this.captcha.destroy(widgetId);
            } else {
                this.widgetId = widgetId;
            }
        } catch {
            this.failed.emit();
        }
    }

    reset(): void {
        if (this.widgetId !== null) {
            this.captcha.reset(this.widgetId);
        }
    }

    ngOnDestroy(): void {
        this.destroyed = true;

        if (this.widgetId !== null) {
            this.captcha.destroy(this.widgetId);
        }
    }
}
