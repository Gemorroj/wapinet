import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { FilterDrawer } from './filter-drawer';

describe('FilterDrawer', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [FilterDrawer],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders projected filters and emits actions', () => {
        const fixture = TestBed.createComponent(FilterDrawer);
        const emitted: string[] = [];

        fixture.componentRef.setInput('open', true);
        fixture.componentInstance.closed.subscribe(() => emitted.push('closed'));
        fixture.componentInstance.applied.subscribe(() => emitted.push('applied'));
        fixture.componentInstance.clear.subscribe(() => emitted.push('clear'));
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('tui-drawer')).not.toBeNull();
        expect(fixture.nativeElement.textContent).toContain('Применить');
        expect(fixture.nativeElement.textContent).toContain('Сбросить');

        const buttons = [...fixture.nativeElement.querySelectorAll('button')] as HTMLButtonElement[];

        buttons.find((button) => button.textContent?.trim() === 'Закрыть')?.click();
        buttons.find((button) => button.textContent?.trim() === 'Применить')?.click();
        buttons.find((button) => button.textContent?.trim() === 'Сбросить')?.click();

        expect(emitted).toEqual(['closed', 'applied', 'clear']);
    });
});
