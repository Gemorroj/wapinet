import { Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TuiCell, TuiNotificationDirective, TuiTitle } from '@taiga-ui/core';

import { UserListItem } from '../../../api/generated/models';
import { UserAvatar } from '../../../shared/user-avatar/user-avatar';

@Component({
    imports: [RouterLink, TuiCell, TuiNotificationDirective, TuiTitle, UserAvatar],
    selector: 'app-user-list',
    templateUrl: './user-list.html',
})
export class UserList {
    readonly items = input.required<UserListItem[]>();
    readonly loading = input(false);
    readonly emptyText = input('Пользователей нет.');
}
