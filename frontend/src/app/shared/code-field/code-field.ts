import { Component, input, output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TuiTextfield } from '@taiga-ui/core';
import { TuiTextarea } from '@taiga-ui/kit/components/textarea';

@Component({
    imports: [FormsModule, TuiTextarea, TuiTextfield],
    selector: 'app-code-field',
    styleUrl: './code-field.css',
    templateUrl: './code-field.html',
})
export class CodeField {
    readonly label = input.required<string>();
    readonly name = input.required<string>();
    readonly value = input.required<string>();
    readonly valueChange = output<string>();
    readonly rows = input(8);
    readonly limit = input<number | null>(null);
    readonly placeholder = input('');
}
