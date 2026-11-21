import { Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiItem } from '@taiga-ui/cdk';
import { TuiLink } from '@taiga-ui/core';
import { TuiBreadcrumbs } from '@taiga-ui/kit/components/breadcrumbs';

export interface Breadcrumb {
    label: string;
    link?: string;
}

@Component({
    imports: [RouterLink, TuiBreadcrumbs, TuiItem, TuiLink],
    selector: 'app-breadcrumbs',
    styleUrl: './breadcrumbs.css',
    templateUrl: './breadcrumbs.html',
})
export class Breadcrumbs {
    @Input({ required: true }) items: Breadcrumb[] = [];
}
