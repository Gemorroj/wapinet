import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

import { API_URL } from './api-url';
import { Auth } from './auth';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
    const auth = inject(Auth);
    const router = inject(Router);
    const apiUrl = inject(API_URL);
    const token = auth.token();
    const isApiRequest = request.url.startsWith(apiUrl);
    const isAuthRequest = request.url.startsWith(`${apiUrl}/auth/`);

    const authorized = isApiRequest && token !== null ? request.clone({ setHeaders: { Authorization: `Bearer ${token}` } }) : request;

    return next(authorized).pipe(
        catchError((error: unknown) => {
            if (isApiRequest && !isAuthRequest && error instanceof HttpErrorResponse && error.status === 401) {
                auth.clearSession();

                if (router.navigated && !router.url.startsWith('/login')) {
                    void router.navigate(['/login'], { queryParams: { returnUrl: router.url } });
                }
            }

            return throwError(() => error);
        }),
    );
};
