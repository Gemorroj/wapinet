import { Component, input } from '@angular/core';
import { TuiAvatar } from '@taiga-ui/kit/components/avatar';
import { TuiBadge } from '@taiga-ui/kit/components/badge';

@Component({
    imports: [TuiAvatar, TuiBadge],
    selector: 'app-user-avatar',
    styleUrl: './user-avatar.css',
    templateUrl: './user-avatar.html',
})
export class UserAvatar {
    readonly avatarUrl = input.required<string>();
    readonly alt = input('');
    readonly isOnline = input(false);
    readonly size = input<'m' | 'l' | 'xl'>('l');
    readonly onlineLabel = input('Онлайн');
}
