import { Component } from '@angular/core';

import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { LinkGrid, LinkGridItem } from '../../shared/link-grid/link-grid';

@Component({
    imports: [Breadcrumbs, LinkGrid],
    selector: 'app-video-courses',
    templateUrl: './video-courses.html',
})
export class VideoCourses {
    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Видео-курсы' }];
    protected readonly links: LinkGridItem[] = [
        {
            title: 'OTUS. Онлайн-образование',
            subtitle: 'Бесплатные уроки и вебинары по программированию, аналитике, машинному обучению и менеджменту.',
            href: 'https://vkvideo.ru/@otusru',
            avatarUrl: '/resources/video_courses/otus.png',
            avatarAlt: 'OTUS',
        },
        {
            title: 'HighLoad++',
            subtitle: 'Доклады с конференций HighLoad++, TokenConf, InoThings++ и РИТ++.',
            href: 'https://www.youtube.com/channel/UCwHL6WHUarjGfUM_586me8w',
            avatarUrl: '/resources/video_courses/highload.png',
            avatarAlt: 'HighLoad++',
        },
        {
            title: 'CodeFest',
            subtitle: 'Канал конференции разработчиков о разработке, управлении проектами и тестировании.',
            href: 'https://www.youtube.com/channel/UCH8zwr3rJOcgCApR_5z31cw',
            avatarUrl: '/resources/video_courses/codefestru.png',
            avatarAlt: 'CodeFest',
        },
        {
            title: 'CutCodeRu',
            subtitle: 'Обучающие материалы по Laravel, PHP и JavaScript.',
            href: 'https://www.youtube.com/@CutCodeRu',
            avatarUrl: '/resources/video_courses/cutcoderu.png',
            avatarAlt: 'CutCodeRu',
        },
        {
            title: 'HabraHabr',
            subtitle: 'Официальный YouTube-канал сообщества «Хабрахабр».',
            href: 'https://www.youtube.com/channel/UCd_sTwKqVrweTt4oAKY5y4w',
            avatarUrl: '/resources/video_courses/habrahabr.png',
            avatarAlt: 'HabraHabr',
        },
        {
            title: 'Postgres Professional',
            subtitle: 'Семинары, лекции и учебные материалы по PostgreSQL.',
            href: 'https://www.youtube.com/channel/UCawnwMqZ6JeoSiEhrS6X69A',
            avatarUrl: '/resources/video_courses/postgres_pro.png',
            avatarAlt: 'Postgres Professional',
        },
        {
            title: 'CodeFreeze',
            subtitle: 'Видео со встреч сообщества CodeFreeze в Петербурге и Москве.',
            href: 'https://www.youtube.com/channel/UC7lx9TaV-jL6vhZyokIJ6NQ',
            avatarUrl: '/resources/video_courses/codefreeze.png',
            avatarAlt: 'CodeFreeze',
        },
        {
            title: 'VK Team',
            subtitle: 'Лекции экспертов, митапы, шоу, онлайн-курсы и подкасты.',
            href: 'https://www.youtube.com/channel/UCmqEpAsQMcsYaeef4qgECvQ',
            avatarUrl: '/resources/video_courses/mailru.png',
            avatarAlt: 'VK Team',
        },
    ];
}
