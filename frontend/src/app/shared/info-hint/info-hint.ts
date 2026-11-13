import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { TuiHintDirective, TuiIcon } from '@taiga-ui/core';

@Component({
    changeDetection: ChangeDetectionStrategy.OnPush,
    imports: [TuiHintDirective, TuiIcon],
    selector: 'app-info-hint',
    styleUrl: './info-hint.css',
    templateUrl: './info-hint.html',
})
export class InfoHint {
    readonly text = input.required<string>();
    readonly label = input<string>('Источник данных');
}
