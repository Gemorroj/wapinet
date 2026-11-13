import { TestBed } from '@angular/core/testing';
import { ActivatedRoute, Router, convertToParamMap, provideRouter } from '@angular/router';
import { provideTaiga } from '@taiga-ui/core';

import { Auth } from '../../core/auth';
import { Login } from './login';

describe('Login', () => {
    const login = vi.fn();
    let authenticated = false;
    let queryParams: Record<string, string> = {};
    let router: Router;

    const auth = {
        authenticated: () => authenticated,
        login,
    };

    const route = {
        get snapshot() {
            return { queryParamMap: convertToParamMap(queryParams) };
        },
    };

    beforeEach(async () => {
        authenticated = false;
        queryParams = {};
        login.mockReset();

        await TestBed.configureTestingModule({
            imports: [Login],
            providers: [{ provide: Auth, useValue: auth }, provideRouter([]), { provide: ActivatedRoute, useValue: route }, provideTaiga()],
        }).compileComponents();

        router = TestBed.inject(Router);
        vi.spyOn(router, 'navigateByUrl').mockResolvedValue(true);
    });

    it('prefills the username after registration', async () => {
        queryParams = { registered: 'bob' };

        const fixture = TestBed.createComponent(Login);

        fixture.detectChanges();
        await fixture.whenStable();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Регистрация завершена');
        expect((fixture.nativeElement.querySelector('input[name="username"]') as HTMLInputElement).value).toBe('bob');
    });

    it('sends the credentials and redirects to the return url', async () => {
        queryParams = { returnUrl: '/gist' };
        login.mockResolvedValue(undefined);

        const fixture = TestBed.createComponent(Login);

        fixture.detectChanges();

        const component = fixture.componentInstance as unknown as { username: string; password: string; submit(): Promise<void> };
        component.username = 'bob';
        component.password = 'secret';

        await component.submit();

        expect(login).toHaveBeenCalledWith('bob', 'secret');
        expect(router.navigateByUrl).toHaveBeenCalledWith('/gist');
    });

    it('shows an error when the credentials are rejected', async () => {
        login.mockRejectedValue(new Error('unauthorized'));

        const fixture = TestBed.createComponent(Login);

        fixture.detectChanges();

        const component = fixture.componentInstance as unknown as { username: string; password: string; submit(): Promise<void> };
        component.username = 'bob';
        component.password = 'wrong';

        await component.submit();
        fixture.detectChanges();

        expect(fixture.nativeElement.textContent).toContain('Неверный логин или пароль.');
        expect(router.navigateByUrl).not.toHaveBeenCalled();
    });

    it('redirects an already authenticated user', () => {
        authenticated = true;

        const fixture = TestBed.createComponent(Login);

        fixture.detectChanges();

        expect(router.navigateByUrl).toHaveBeenCalledWith('/files');
    });
});
