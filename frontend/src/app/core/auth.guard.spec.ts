import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, RouterStateSnapshot, UrlTree, provideRouter } from '@angular/router';

import { Auth } from './auth';
import { adminGuard, authGuard } from './auth.guard';

describe('authGuard', () => {
    function configure(auth: Record<string, unknown>): void {
        TestBed.configureTestingModule({
            providers: [{ provide: Auth, useValue: auth }, provideRouter([])],
        });
    }

    function run(guard: typeof authGuard): boolean | UrlTree {
        return TestBed.runInInjectionContext(() => guard({} as ActivatedRouteSnapshot, { url: '/files' } as RouterStateSnapshot)) as boolean | UrlTree;
    }

    it('allows authenticated users', () => {
        configure({ authenticated: () => true });

        expect(run(authGuard)).toBe(true);
    });

    it('redirects anonymous users to login with returnUrl', () => {
        configure({ authenticated: () => false });

        const result = run(authGuard) as UrlTree;

        expect(result.toString()).toBe('/login?returnUrl=%2Ffiles');
    });
});

describe('adminGuard', () => {
    function configure(auth: Record<string, unknown>): void {
        TestBed.configureTestingModule({
            providers: [{ provide: Auth, useValue: auth }, provideRouter([])],
        });
    }

    function run(): boolean | UrlTree {
        return TestBed.runInInjectionContext(() => adminGuard({} as ActivatedRouteSnapshot, { url: '/files/hidden' } as RouterStateSnapshot)) as
            boolean | UrlTree;
    }

    it('redirects anonymous users to login', () => {
        configure({ authenticated: () => false });

        expect((run() as UrlTree).toString()).toBe('/login?returnUrl=%2Ffiles%2Fhidden');
    });

    it('redirects authenticated non-admin users to forbidden', () => {
        configure({ authenticated: () => true, isAdmin: () => false });

        expect((run() as UrlTree).toString()).toBe('/forbidden');
    });

    it('allows administrators', () => {
        configure({ authenticated: () => true, isAdmin: () => true });

        expect(run()).toBe(true);
    });
});
