import { Component, OnInit, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { TuiButton, TuiDataList, TuiDropdown, TuiIcon, TuiLink, TuiRoot } from '@taiga-ui/core';

import { Auth } from './core/auth';
import { SiteStats } from './core/site-stats';

interface NavItem {
    readonly label: string;
    readonly link: string;
    readonly external?: boolean;
}

@Component({
    imports: [RouterLink, RouterLinkActive, RouterOutlet, TuiButton, TuiDataList, TuiDropdown, TuiIcon, TuiLink, TuiRoot],
    selector: 'app-root',
    styleUrl: './app.css',
    templateUrl: './app.html',
})
export class App implements OnInit {
    protected readonly auth = inject(Auth);
    private readonly router = inject(Router);
    private readonly siteStats = inject(SiteStats);

    protected readonly navItems: readonly NavItem[] = [
        { label: 'Главная', link: '/' },
        { label: 'Файлообменник', link: '/files' },
        { label: 'Форум', link: 'https://forum.wapinet.ru', external: true },
        { label: 'Блоги', link: '/gist' },
        { label: 'Гостевая', link: '/guestbook' },
        { label: 'Новости', link: '/news' },
    ];
    protected readonly stats = this.siteStats.value;
    protected readonly menuOpen = signal(false);

    async ngOnInit(): Promise<void> {
        await this.siteStats.load();
    }

    protected closeMenu(): void {
        this.menuOpen.set(false);
    }

    protected async logout(): Promise<void> {
        this.closeMenu();
        await this.auth.logout();
        await this.router.navigateByUrl('/');
    }
}
