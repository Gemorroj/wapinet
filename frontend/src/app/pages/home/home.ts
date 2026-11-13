import { Component, OnInit, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiCell, TuiIcon, TuiTitle } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { SiteStats } from '../../core/site-stats';

@Component({
    imports: [RouterLink, TuiCardLarge, TuiCell, TuiIcon, TuiTitle],
    selector: 'app-home',
    styleUrl: './home.css',
    templateUrl: './home.html',
})
export class Home implements OnInit {
    private readonly siteStats = inject(SiteStats);

    protected readonly stats = this.siteStats.value;

    async ngOnInit(): Promise<void> {
        await this.siteStats.load();
    }
}
