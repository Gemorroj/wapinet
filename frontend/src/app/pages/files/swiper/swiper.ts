import { Component, HostListener, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TuiButton, TuiIcon, TuiLoader } from '@taiga-ui/core';
import { TuiCardLarge } from '@taiga-ui/layout';

import { FileResponse } from '../../../api/generated/models';
import { FilesApi } from '../../../core/api/files-api';
import { describeError } from '../../../core/api/problem-details';

@Component({
    imports: [RouterLink, TuiButton, TuiIcon, TuiCardLarge, TuiLoader],
    selector: 'app-file-swiper',
    styleUrl: './swiper.css',
    templateUrl: './swiper.html',
})
export class FileSwiper implements OnInit {
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly filesApi = inject(FilesApi);

    protected readonly file = signal<FileResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly error = signal<string | null>(null);
    protected fileId = 0;
    private loadToken = 0;

    ngOnInit(): void {
        this.route.paramMap.subscribe((params) => {
            this.fileId = Number(params.get('id'));
            void this.load();
        });
    }

    @HostListener('document:keydown', ['$event'])
    protected onKeydown(event: KeyboardEvent): void {
        if (event.key === 'ArrowLeft') {
            void this.goPrev();
        } else if (event.key === 'ArrowRight') {
            void this.goNext();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            void this.close();
        }
    }

    protected async close(): Promise<void> {
        await this.router.navigate(['/files', this.fileId]);
    }

    protected async goPrev(): Promise<void> {
        const id = this.file()?.navigation.prevId;

        if (id) {
            await this.router.navigate(['/files/swiper', id]);
        }
    }

    protected async goNext(): Promise<void> {
        const id = this.file()?.navigation.nextId;

        if (id) {
            await this.router.navigate(['/files/swiper', id]);
        }
    }

    private async load(): Promise<void> {
        const token = ++this.loadToken;

        this.file.set(null);

        if (!Number.isInteger(this.fileId) || this.fileId <= 0) {
            this.error.set('Файл не найден.');
            this.loading.set(false);

            return;
        }

        this.loading.set(true);
        this.error.set(null);

        try {
            const file = await this.filesApi.show(this.fileId);

            if (token === this.loadToken) {
                this.file.set(file);
            }
        } catch (error) {
            if (token !== this.loadToken) {
                return;
            }

            this.error.set(describeError(error));
            this.file.set(null);
        } finally {
            if (token === this.loadToken) {
                this.loading.set(false);
            }
        }
    }
}
