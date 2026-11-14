import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

import { Auth } from './auth';

export const authGuard: CanActivateFn = (_route, state) => {
    const auth = inject(Auth);
    const router = inject(Router);

    return auth.authenticated() ? true : router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } });
};

export const adminGuard: CanActivateFn = (_route, state) => {
    const auth = inject(Auth);
    const router = inject(Router);

    if (!auth.authenticated()) {
        return router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } });
    }

    return auth.isAdmin() ? true : router.createUrlTree(['/forbidden']);
};
