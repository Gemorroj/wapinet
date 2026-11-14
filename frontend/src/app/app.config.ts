import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { ApplicationConfig, inject, provideAppInitializer, provideBrowserGlobalErrorListeners, signal } from '@angular/core';
import { RouteReuseStrategy, provideRouter, withComponentInputBinding, withInMemoryScrolling, withViewTransitions } from '@angular/router';
import { provideTaiga, tuiAssetsPathProvider } from '@taiga-ui/core';
import { TUI_LANGUAGE, TUI_RUSSIAN_LANGUAGE } from '@taiga-ui/i18n';
import { tuiBreadcrumbsOptionsProvider } from '@taiga-ui/kit/components/breadcrumbs';

import { Auth } from './core/auth/auth';
import { authInterceptor } from './core/auth/auth-interceptor';
import { routes } from './app.routes';
import { API_URL } from './core/api/api-url';
import { ToolRouteReuseStrategy } from './core/router/tool-route-reuse-strategy';

export const appConfig: ApplicationConfig = {
    providers: [
        provideBrowserGlobalErrorListeners(),
        provideRouter(routes, withComponentInputBinding(), withInMemoryScrolling({ scrollPositionRestoration: 'enabled' }), withViewTransitions()),
        // FetchBackend is the default in Angular 22
        provideHttpClient(withInterceptors([authInterceptor])),
        provideAppInitializer(() => inject(Auth).loadMe()),
        provideTaiga(),
        // The icon pack lives with the other static resources, outside the SPA build.
        tuiAssetsPathProvider('/resources/taiga-ui/icons'),
        // Taiga UI ships English by default; the site is Russian.
        { provide: TUI_LANGUAGE, useValue: signal(TUI_RUSSIAN_LANGUAGE) },
        // A slash reads as a path separator, which fits the site's URLs.
        tuiBreadcrumbsOptionsProvider({ icon: '/' }),
        // The API paths already start with /api; API_URL identifies them for the interceptor.
        { provide: API_URL, useValue: '/api' },
        { provide: RouteReuseStrategy, useClass: ToolRouteReuseStrategy },
    ],
};
