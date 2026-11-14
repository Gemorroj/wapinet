import { Component, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { AppConfigService } from '../../core/app-config';
import { UsersApi } from '../../core/api/users-api';
import { YandexCaptcha } from '../../core/auth/yandex-captcha';
import { Registration } from './registration';

@Component({ template: '' })
class Stub {}

describe('Registration', () => {
    const register = vi.fn().mockResolvedValue({ username: 'alice' });
    const appConfig = { config: signal(null), load: vi.fn().mockResolvedValue(undefined) };
    const captcha = { render: vi.fn().mockResolvedValue(1), reset: vi.fn(), destroy: vi.fn() };

    const fill = (fixture: ReturnType<typeof TestBed.createComponent<Registration>>, values: string[]): void => {
        const inputs = fixture.nativeElement.querySelectorAll('input') as NodeListOf<HTMLInputElement>;

        values.forEach((value, index) => {
            inputs[index].value = value;
            inputs[index].dispatchEvent(new Event('input'));
        });
    };

    beforeEach(async () => {
        register.mockClear();
        appConfig.load.mockClear();
        appConfig.config.set(null);
        captcha.render.mockClear();
        captcha.reset.mockClear();

        await TestBed.configureTestingModule({
            imports: [Registration],
            providers: [
                { provide: UsersApi, useValue: { register } },
                { provide: AppConfigService, useValue: appConfig },
                { provide: YandexCaptcha, useValue: captcha },
                provideRouter([{ path: 'login', component: Stub }]),
                provideTaiga(),
            ],
        }).compileComponents();
    });

    it('registers a new user', async () => {
        const fixture = TestBed.createComponent(Registration);

        await fixture.whenStable();
        fixture.detectChanges();

        fill(fixture, ['alice', 'alice@example.com', 'secret123', 'secret123']);
        await fixture.whenStable();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('submit'));
        await fixture.whenStable();

        expect(register).toHaveBeenCalledWith({
            username: 'alice',
            email: 'alice@example.com',
            password: 'secret123',
            captchaToken: null,
        });

        fixture.destroy();
    });

    it('rejects mismatched passwords', async () => {
        const fixture = TestBed.createComponent(Registration);

        await fixture.whenStable();
        fixture.detectChanges();

        fill(fixture, ['alice', 'alice@example.com', 'secret123', 'secret456']);
        await fixture.whenStable();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('submit'));
        await fixture.whenStable();
        fixture.detectChanges();

        expect(register).not.toHaveBeenCalled();
        expect(fixture.nativeElement.textContent).toContain('Пароли не совпадают.');

        fixture.destroy();
    });

    it('loads the captcha only after the form is touched', async () => {
        appConfig.config.set({ captcha: { enabled: true, siteKey: 'test' } } as never);

        const fixture = TestBed.createComponent(Registration);

        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('app-captcha')).toBeNull();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('focusin', { bubbles: true }));
        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('app-captcha')).toBeTruthy();

        fixture.destroy();
    });

    it('asks for the captcha before registering', async () => {
        appConfig.config.set({ captcha: { enabled: true, siteKey: 'test' } } as never);

        const fixture = TestBed.createComponent(Registration);

        await fixture.whenStable();
        fixture.detectChanges();

        fill(fixture, ['alice', 'alice@example.com', 'secret123', 'secret123']);
        await fixture.whenStable();

        const form = fixture.nativeElement.querySelector('form') as HTMLFormElement;

        form.dispatchEvent(new Event('submit'));
        fixture.detectChanges();
        await fixture.whenStable();

        expect(register).not.toHaveBeenCalled();
        expect(fixture.nativeElement.textContent).toContain('Подтвердите, что вы не робот.');

        fixture.destroy();
    });
});
