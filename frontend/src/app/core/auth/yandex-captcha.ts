import { Injectable } from '@angular/core';

interface SmartCaptchaApi {
    render(
        container: HTMLElement,
        options: {
            sitekey: string;
            callback: (token: string) => void;
            hl?: string;
        },
    ): number;
    reset?(widgetId: number): void;
    destroy?(widgetId: number): void;
}

declare global {
    interface Window {
        smartCaptcha?: SmartCaptchaApi;
        __smartCaptchaOnload?: () => void;
    }
}

@Injectable({ providedIn: 'root' })
export class YandexCaptcha {
    private scriptPromise: Promise<void> | null = null;

    async render(container: HTMLElement, siteKey: string, onToken: (token: string) => void): Promise<number> {
        await this.load();

        const api = window.smartCaptcha;

        if (!api) {
            throw new Error('SmartCaptcha is not available.');
        }

        return api.render(container, {
            sitekey: siteKey,
            callback: onToken,
            hl: 'ru',
        });
    }

    reset(widgetId: number): void {
        window.smartCaptcha?.reset?.(widgetId);
    }

    destroy(widgetId: number): void {
        window.smartCaptcha?.destroy?.(widgetId);
    }

    private load(): Promise<void> {
        if (window.smartCaptcha) {
            return Promise.resolve();
        }

        if (this.scriptPromise !== null) {
            return this.scriptPromise;
        }

        this.scriptPromise = new Promise<void>((resolve, reject) => {
            window.__smartCaptchaOnload = () => resolve();

            const script = document.createElement('script');

            script.src = 'https://smartcaptcha.cloud.yandex.ru/captcha.js?render=onload&onload=__smartCaptchaOnload';
            script.async = true;
            script.defer = true;
            script.onerror = () => {
                this.scriptPromise = null;
                reject(new Error('Unable to load SmartCaptcha.'));
            };

            document.head.appendChild(script);
        });

        return this.scriptPromise;
    }
}
