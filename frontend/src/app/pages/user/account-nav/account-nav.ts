import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';

@Component({
    imports: [RouterLink, RouterLinkActive, TuiTabs],
    selector: 'app-account-nav',
    templateUrl: './account-nav.html',
})
export class AccountNav {}
