import { Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiButton } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

@Component({
    imports: [RouterLink, TuiButton, TuiCardLarge],
    selector: 'app-status-page',
    styleUrl: './status-page.css',
    templateUrl: './status-page.html',
})
export class StatusPage {
    readonly title = input.required<string>();
    readonly message = input.required<string>();
}
