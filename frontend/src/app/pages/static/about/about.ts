import { Component } from '@angular/core';
import { TuiLink } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';

@Component({
    imports: [Breadcrumbs, TuiCardLarge, TuiLink],
    selector: 'app-about',
    styleUrl: './about.css',
    templateUrl: './about.html',
})
export class About {
    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'О сайте' }];
}
