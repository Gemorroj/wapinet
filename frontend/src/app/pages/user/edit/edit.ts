import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { TuiButton, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiDropdown, tuiItemsHandlersProvider } from '@taiga-ui/core';
import { TuiDataListWrapper } from '@taiga-ui/kit/components/data-list-wrapper';
import { TuiSelect } from '@taiga-ui/kit/components/select';
import { TuiChevron } from '@taiga-ui/kit/directives/chevron';
import { TuiCardLarge } from '@taiga-ui/layout';

import { AppConfigService } from '../../../core/app-config';
import { Auth } from '../../../core/auth/auth';
import { countryFlagUrl } from '../../../core/browser/country-flag';
import { describeError } from '../../../core/api/problem-details';
import { UsersApi } from '../../../core/api/users-api';
import { AccountNav } from '../account-nav/account-nav';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { BbcodeEditor } from '../../../shared/bbcode-editor/bbcode-editor';

type Sex = 'm' | 'f' | '';

interface SelectOption<T extends string = string> {
    value: T;
    label: string;
}

@Component({
    imports: [
        AccountNav,
        Breadcrumbs,
        BbcodeEditor,
        FormsModule,
        RouterLink,
        TuiButton,
        TuiCardLarge,
        TuiChevron,
        TuiDataListWrapper,
        TuiDropdown,
        TuiInput,
        TuiLoader,
        TuiNotificationDirective,
        TuiSelect,
        TuiTextfield,
    ],
    providers: [
        tuiItemsHandlersProvider<SelectOption>({
            stringify: signal((item) => item.label),
            identityMatcher: signal((first, second) => first.value === second.value),
        }),
    ],
    selector: 'app-user-edit',
    styleUrl: './edit.css',
    templateUrl: './edit.html',
})
export class UserEdit implements OnInit {
    private readonly usersApi = inject(UsersApi);
    private readonly appConfig = inject(AppConfigService);
    private readonly auth = inject(Auth);
    private readonly router = inject(Router);

    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Профиль', link: '/user/profile' }, { label: 'Редактирование' }];
    protected readonly loading = signal(false);
    protected readonly saving = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly sexOptions: SelectOption<Sex>[] = [
        { value: '', label: 'Не указан' },
        { value: 'm', label: 'Мужской' },
        { value: 'f', label: 'Женский' },
    ];
    protected readonly countryOptions = signal<SelectOption[]>([{ value: '', label: 'Не указана' }]);
    protected readonly timezoneOptions = signal<SelectOption[]>([{ value: '', label: 'Не указана' }]);
    protected readonly username = signal('');
    protected readonly sex = signal<SelectOption<Sex>>(this.sexOptions[0]);
    protected readonly birthday = signal('');
    protected readonly timezone = signal<SelectOption>({ value: '', label: 'Не указана' });
    protected readonly country = signal<SelectOption>({ value: '', label: 'Не указана' });
    protected readonly vk = signal('');
    protected readonly info = signal('');
    protected readonly flagUrl = countryFlagUrl;

    async ngOnInit(): Promise<void> {
        const user = this.auth.user();

        if (null === user) {
            return;
        }

        this.username.set(user.username);
        this.loading.set(true);

        try {
            const config = await this.appConfig.load();
            const profile = await this.usersApi.profile(user.username);

            this.countryOptions.set([{ value: '', label: 'Не указана' }, ...config.countries.map((country) => ({ value: country.code, label: country.name }))]);
            this.timezoneOptions.set([
                { value: '', label: 'Не указана' },
                ...config.timezones.map((timezone) => ({ value: timezone.id, label: timezone.name })),
            ]);

            this.sex.set(this.findOption(this.sexOptions, profile.sex ?? ''));
            this.country.set(this.findOption(this.countryOptions(), profile.country ?? ''));
            this.timezone.set(this.findOption(this.timezoneOptions(), profile.timezone ?? ''));
            this.birthday.set(profile.birthday ?? '');
            this.vk.set(profile.vk ?? '');
            this.info.set(profile.info ?? '');
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected async submit(): Promise<void> {
        this.error.set(null);
        this.saving.set(true);

        try {
            await this.usersApi.updateProfile({
                sex: this.sex().value || null,
                birthday: this.birthday() || null,
                timezone: this.timezone().value || null,
                country: this.country().value || null,
                vk: this.vk() || null,
                info: this.info() || null,
            });

            await this.router.navigate(['/user/profile', this.username()]);
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.saving.set(false);
        }
    }

    private findOption<T extends string>(options: SelectOption<T>[], value: string): SelectOption<T> {
        return options.find((option) => option.value === value) ?? options[0];
    }
}
