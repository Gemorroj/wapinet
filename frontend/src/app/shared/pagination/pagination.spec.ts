import { TestBed } from '@angular/core/testing';
import { provideTaiga } from '@taiga-ui/core';

import { Pagination } from './pagination';

describe('Pagination', () => {
    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [Pagination],
            providers: [provideTaiga()],
        }).compileComponents();
    });

    it('renders navigation only when there is more than one page', () => {
        const fixture = TestBed.createComponent(Pagination);

        fixture.componentRef.setInput('total', 10);
        fixture.componentRef.setInput('perPage', 10);
        fixture.componentRef.setInput('page', 1);
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('tui-pagination')).toBeNull();

        fixture.componentRef.setInput('total', 30);
        fixture.detectChanges();

        expect(fixture.nativeElement.querySelector('tui-pagination')).not.toBeNull();
    });
});
