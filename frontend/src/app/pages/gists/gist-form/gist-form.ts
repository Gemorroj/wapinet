import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { TuiButton, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { GistResponse } from '../../../api/generated/models';
import { GistsApi } from '../../../core/gists-api';
import { describeError } from '../../../core/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../../shared/breadcrumbs/breadcrumbs';
import { BbcodeEditor } from '../../../shared/bbcode-editor/bbcode-editor';

@Component({
    imports: [BbcodeEditor, Breadcrumbs, FormsModule, TuiButton, TuiCardLarge, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield],
    selector: 'app-gist-form',
    styleUrl: './gist-form.css',
    templateUrl: './gist-form.html',
})
export class GistForm implements OnInit {
    private readonly gistsApi = inject(GistsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);

    protected readonly gistId = signal<number | null>(null);
    protected readonly editing = computed(() => null !== this.gistId());
    protected readonly current = signal<GistResponse | null>(null);
    protected readonly subject = signal('');
    protected readonly body = signal('');
    protected readonly bCheck = signal('');
    protected readonly loading = signal(false);
    protected readonly saving = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const items: Breadcrumb[] = [
            { label: 'Главная', link: '/' },
            { label: 'Блоги', link: '/gist' },
        ];
        const current = this.current();

        if (null !== current) {
            items.push({ label: current.subject, link: `/gist/view/${current.id}` });
        }

        items.push({ label: this.editing() ? 'Редактирование' : 'Новая запись' });

        return items;
    });

    async ngOnInit(): Promise<void> {
        const id = Number(this.route.snapshot.paramMap.get('id'));

        if (!Number.isInteger(id) || id <= 0) {
            return;
        }

        this.gistId.set(id);
        this.loading.set(true);

        try {
            const gist = await this.gistsApi.show(id);

            this.current.set(gist);
            this.subject.set(gist.subject);
            this.body.set(gist.body);

            if (!gist.canEdit) {
                this.error.set('Недостаточно прав для редактирования записи.');
            }
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected async save(): Promise<void> {
        const subject = this.subject().trim();
        const body = this.body().trim();

        if ('' === subject) {
            this.error.set('Укажите тему записи.');
            return;
        }

        if ('' === body) {
            this.error.set('Введите текст записи.');
            return;
        }

        const id = this.gistId();

        this.saving.set(true);
        this.error.set(null);

        try {
            if (null === id) {
                const gist = await this.gistsApi.create({
                    subject,
                    body,
                    bCheck: this.bCheck() || null,
                });

                await this.router.navigate(['/gist/view', gist.id]);
            } else {
                await this.gistsApi.update(id, { subject, body });
                await this.router.navigate(['/gist/view', id]);
            }
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.saving.set(false);
        }
    }

    protected async cancel(): Promise<void> {
        const id = this.gistId();

        await this.router.navigate(null !== id ? ['/gist/view', id] : ['/gist']);
    }
}
