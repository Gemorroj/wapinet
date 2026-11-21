import { Component, computed, input, output } from '@angular/core';
import { TuiPagination } from '@taiga-ui/kit/components/pagination';

@Component({
    imports: [TuiPagination],
    selector: 'app-pagination',
    templateUrl: './pagination.html',
})
export class Pagination {
    readonly total = input.required<number>();
    readonly perPage = input.required<number>();
    readonly page = input.required<number>();
    readonly pageChange = output<number>();

    protected readonly pages = computed(() => Math.max(1, Math.ceil(this.total() / Math.max(1, this.perPage()))));
}
