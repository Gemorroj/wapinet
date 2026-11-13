import { Component, HostListener, input, output } from '@angular/core';
import { TuiButton, TuiTitle } from '@taiga-ui/core';
import { TuiDrawer } from '@taiga-ui/kit/components/drawer';

@Component({
    imports: [TuiButton, TuiDrawer, TuiTitle],
    selector: 'app-filter-drawer',
    styleUrl: './filter-drawer.css',
    templateUrl: './filter-drawer.html',
})
export class FilterDrawer {
    readonly open = input.required<boolean>();
    readonly title = input('Фильтры');
    readonly closed = output<void>();
    readonly applied = output<void>();
    readonly clear = output<void>();

    @HostListener('document:keydown.escape')
    protected closeOnEscape(): void {
        if (this.open()) {
            this.closed.emit();
        }
    }
}
