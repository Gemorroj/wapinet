import { Component, input } from '@angular/core';
import { TuiTitle } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

@Component({
    imports: [TuiCardLarge, TuiTitle],
    selector: 'app-help-section',
    styleUrl: './help-section.css',
    templateUrl: './help-section.html',
})
export class HelpSection {
    readonly title = input.required<string>();
}
