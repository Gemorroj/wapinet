import { Component } from '@angular/core';

import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { LinkGrid, LinkGridItem } from '../../shared/link-grid/link-grid';

@Component({
    imports: [Breadcrumbs, LinkGrid],
    selector: 'app-politics',
    templateUrl: './politics.html',
})
export class Politics {
    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Политика' }];
    protected readonly links: LinkGridItem[] = [
        {
            title: 'ПриZрак Новороссии',
            subtitle: 'Владимир Грубник — бывший хирург, к.м.н., диверсант-подпольщик.',
            href: 'https://t.me/s/ghost_of_novorossia',
            avatarUrl: '/resources/politics/ghost_of_novorossia.png',
            avatarAlt: 'ПриZрак Новороссии',
        },
        {
            title: 'Colonelcassad',
            subtitle: 'Авторский канал Бориса Рожина — эксперта Центра военно-политической журналистики.',
            href: 'https://t.me/s/boris_rozhin',
            avatarUrl: '/resources/politics/boris_rozhin.png',
            avatarAlt: 'Colonelcassad',
        },
        {
            title: 'Кирилл Фёдоров / Война История Оружие',
            subtitle: 'Новости, политика, обзоры техники, архивные материалы и прямые трансляции.',
            href: 'https://t.me/s/warhistoryalconafter',
            avatarUrl: '/resources/politics/warhistoryalconafter.png',
            avatarAlt: 'Кирилл Фёдоров',
        },
        {
            title: 'Рыбарь',
            subtitle: 'Вылавливаем интересную тему в море сырой информации.',
            href: 'https://t.me/s/rybar',
            avatarUrl: '/resources/politics/rybar.png',
            avatarAlt: 'Рыбарь',
        },
        {
            title: 'Два майора',
            subtitle: 'Освещение СВО, поддержка фронта, мировые новости, военная аналитика и юмор.',
            href: 'https://t.me/s/dva_majors',
            avatarUrl: '/resources/politics/dva_majors.png',
            avatarAlt: 'Два майора',
        },
        {
            title: 'РОГОЗИН',
            subtitle: 'Сенатор, командир, начальник ЦСН «Барс-Сармат».',
            href: 'https://t.me/s/rogozin_do',
            avatarUrl: '/resources/politics/rogozin_do.png',
            avatarAlt: 'РОГОЗИН',
        },
        {
            title: 'Репортер Filatov',
            subtitle: 'Российский журналист, военный корреспондент.',
            href: 'https://t.me/s/filatovcorr',
            avatarUrl: '/resources/politics/filatovcorr.png',
            avatarAlt: 'Репортер Filatov',
        },
    ];
}
