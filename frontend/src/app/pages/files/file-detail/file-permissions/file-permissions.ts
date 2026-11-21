import { Component, computed, input } from '@angular/core';
import { TuiTitle } from '@taiga-ui/core';
import { TuiBadge } from '@taiga-ui/kit/components/badge';

import { FilePermission } from '../../../../api/generated/models';

@Component({
    imports: [TuiBadge, TuiTitle],
    selector: 'app-file-permissions',
    styleUrl: './file-permissions.css',
    templateUrl: './file-permissions.html',
})
export class FilePermissions {
    readonly permissions = input.required<FilePermission[]>();

    /** Permissions that deserve attention stay visible, the rest is collapsed. */
    protected readonly risky = computed(() => this.permissions().filter((permission) => permission.danger || permission.warning || permission.cost));

    protected readonly other = computed(() => this.permissions().filter((permission) => !permission.danger && !permission.warning && !permission.cost));
}
