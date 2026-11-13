import { Component } from '@angular/core';
import { TuiLink } from '@taiga-ui/core';

import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { LinkGrid, LinkGridItem } from '../../../shared/link-grid/link-grid';

@Component({
    imports: [Breadcrumbs, LinkGrid, TuiLink],
    selector: 'app-open-source',
    templateUrl: './open-source.html',
})
export class OpenSource {
    protected readonly breadcrumbs: Breadcrumb[] = [{ label: 'Главная', link: '/' }, { label: 'Open source' }];
    protected readonly links: LinkGridItem[] = [
        {
            title: 'wapinet.ru',
            subtitle: 'Исходники этого сайта',
            href: 'https://github.com/Gemorroj/wapinet',
        },
        {
            title: 'Archive 7z',
            subtitle: 'Обёртка над 7-Zip: 7z, zip, tar, gzip, xz и др.',
            href: 'https://github.com/Gemorroj/Archive7z',
        },
        {
            title: 'M3u Parser',
            subtitle: 'Парсер и генератор m3u-плейлистов',
            href: 'https://github.com/Gemorroj/M3uParser',
        },
        {
            title: 'xBBCode',
            subtitle: 'Парсер BBCode',
            href: 'https://github.com/Gemorroj/xBBCode',
        },
        {
            title: 'Rss',
            subtitle: 'RSS 2.0: парсер и генератор',
            href: 'https://github.com/Gemorroj/Rss',
        },
        {
            title: 'Ginfo',
            subtitle: 'Состояние сервера: железо, сервисы, SMTP',
            href: 'https://github.com/Gemorroj/ginfo',
        },
        {
            title: 'StopSpam',
            subtitle: 'Проверка IP и e-mail на спам',
            href: 'https://github.com/Gemorroj/StopSpam',
        },
        {
            title: 'Syntax',
            subtitle: 'Проверка синтаксиса PHP',
            href: 'https://github.com/Gemorroj/Syntax',
        },
        {
            title: 'PHP Obfuscator',
            subtitle: 'Обфускация PHP-кода через AST',
            href: 'https://github.com/Gemorroj/php-obfuscator',
        },
        {
            title: 'Logio',
            subtitle: 'Парсер логов',
            href: 'https://github.com/Gemorroj/Logio',
        },
        {
            title: 'CSSValidator',
            subtitle: 'Клиент W3C CSS Validator',
            href: 'https://github.com/Gemorroj/CSSValidator',
        },
        {
            title: 'HTMLValidator',
            subtitle: 'Клиент W3C HTML Validator',
            href: 'https://github.com/Gemorroj/HTMLValidator',
        },
        {
            title: 'WhoRdap',
            subtitle: 'WHOIS и RDAP для доменов, IP и ASN',
            href: 'https://github.com/Gemorroj/whordap',
        },
        {
            title: 'EbookReader',
            subtitle: 'Чтение EPUB, MOBI, FB2 и FB2-ZIP',
            href: 'https://github.com/Gemorroj/EbookReader',
        },
    ];
}
