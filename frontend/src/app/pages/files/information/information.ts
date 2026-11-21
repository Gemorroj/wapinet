import { Component } from '@angular/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';

@Component({
    imports: [Breadcrumbs, TuiCardLarge],
    selector: 'app-file-information',
    styleUrl: './information.css',
    templateUrl: './information.html',
})
export class FileInformation {
    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Файлообменник', link: '/files' }, { label: 'Информация' }];
}
