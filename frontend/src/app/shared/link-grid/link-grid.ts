import { NgTemplateOutlet } from '@angular/common';
import { Component, input } from '@angular/core';
import { TuiCell, TuiLink, TuiTitle } from '@taiga-ui/core';
import { TuiAvatar } from '@taiga-ui/kit/components/avatar';

export interface LinkGridExtraLink {
    readonly label: string;
    readonly href: string;
}

export interface LinkGridItem {
    readonly title: string;
    readonly subtitle?: string;
    readonly href: string;
    readonly avatarUrl?: string;
    readonly avatarAlt?: string;
    readonly extraLinks?: LinkGridExtraLink[];
}

@Component({
    imports: [NgTemplateOutlet, TuiAvatar, TuiCell, TuiLink, TuiTitle],
    selector: 'app-link-grid',
    styleUrl: './link-grid.css',
    templateUrl: './link-grid.html',
})
export class LinkGrid {
    readonly items = input.required<LinkGridItem[]>();
    /** Portrait images (book covers) can be shown in full instead of cropped to a square. */
    readonly imageFit = input<'cover' | 'contain'>('cover');
}
