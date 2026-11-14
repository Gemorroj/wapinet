import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Data, Router } from '@angular/router';
import { TuiButton, TuiCheckbox, TuiDropdown, TuiInput, TuiLoader, TuiNotificationDirective, TuiTextfield, TuiTitle } from '@taiga-ui/core';
import { TuiComboBox } from '@taiga-ui/kit/components/combo-box';
import { TuiDataListWrapper } from '@taiga-ui/kit/components/data-list-wrapper';
import { TuiTabs } from '@taiga-ui/kit/components/tabs';
import { TuiTextarea } from '@taiga-ui/kit/components/textarea';
import { TuiChevron } from '@taiga-ui/kit/directives/chevron';
import { TuiCardLarge } from '@taiga-ui/layout';

import { AudioTagsInfoResponse } from '../../api/generated/models';
import { AudioTagsApi } from '../../core/api/audio-tags-api';
import { parseAudioFileName } from './filename';
import { GENRES } from './audio-tags-genres';
import { formatBytes } from '../../core/format/format-bytes';
import { describeError } from '../../core/api/problem-details';
import { Breadcrumb, Breadcrumbs } from '../../shared/breadcrumbs/breadcrumbs';
import { FileSource } from '../../shared/file-source/file-source';
import { HelpSection } from '../../shared/help-section/help-section';

interface AudioTagsTab {
    readonly id: 'tool' | 'about';
    readonly label: string;
    readonly title: string;
    readonly path: string;
}

const AUDIO_TAGS_TABS: readonly AudioTagsTab[] = [
    { id: 'tool', label: 'Редактор', title: 'Аудио-теги', path: '/audio_tags' },
    { id: 'about', label: 'Справка', title: 'Об аудио-тегах', path: '/audio_tags/about' },
];

@Component({
    imports: [
        Breadcrumbs,
        FileSource,
        FormsModule,
        HelpSection,
        TuiButton,
        TuiCardLarge,
        TuiCheckbox,
        TuiChevron,
        TuiComboBox,
        TuiDataListWrapper,
        TuiDropdown,
        TuiInput,
        TuiLoader,
        TuiNotificationDirective,
        TuiTabs,
        TuiTextarea,
        TuiTextfield,
        TuiTitle,
    ],
    selector: 'app-audio-tags',
    styleUrl: './audio-tags.css',
    templateUrl: './audio-tags.html',
})
export class AudioTags implements OnInit {
    private readonly api = inject(AudioTagsApi);
    private readonly route = inject(ActivatedRoute);
    private readonly router = inject(Router);
    private readonly destroyRef = inject(DestroyRef);

    constructor() {
        this.destroyRef.onDestroy(() => this.revokeCover());
    }

    protected readonly tabs = AUDIO_TAGS_TABS;
    protected readonly tabIndex = signal(0);
    protected readonly sourceFiles = signal<File[]>([]);
    protected readonly sourceUrl = signal('');
    protected readonly info = signal<AudioTagsInfoResponse | null>(null);
    protected readonly loading = signal(false);
    protected readonly saving = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly notice = signal<string | null>(null);
    protected readonly title = signal('');
    protected readonly albumArtist = signal('');
    protected readonly artist = signal('');
    protected readonly album = signal('');
    protected readonly year = signal('');
    protected readonly trackNumber = signal('');
    protected readonly urlUser = signal('');
    protected readonly genre = signal('');
    protected readonly genreQuery = signal('');
    protected readonly genreSuggestions = computed(() => {
        const query = this.genreQuery().trim().toLocaleLowerCase();

        return '' === query ? GENRES : GENRES.filter((genre) => genre.toLocaleLowerCase().includes(query));
    });
    protected readonly comment = signal('');
    protected readonly composer = signal('');
    protected readonly discNumber = signal('');
    protected readonly copyright = signal('');
    protected readonly lyrics = signal('');
    protected readonly bpm = signal('');
    protected readonly isrc = signal('');
    protected readonly removeOtherTags = signal(false);
    protected readonly removePicture = signal(false);
    protected readonly coverFiles = signal<File[]>([]);
    protected readonly coverUrl = signal('');
    protected readonly coverVersion = signal(0);
    protected readonly coverObjectUrl = signal<string | null>(null);
    protected readonly pendingClear = signal(false);
    protected readonly clearing = signal(false);
    protected readonly pendingReset = signal(false);
    protected readonly resetting = signal(false);
    protected readonly breadcrumbs = computed<Breadcrumb[]>(() => {
        const tab = AUDIO_TAGS_TABS[this.tabIndex()] ?? AUDIO_TAGS_TABS[0];

        return 0 === this.tabIndex()
            ? [{ label: 'Главная', link: '/' }, { label: 'Аудио-теги' }]
            : [{ label: 'Главная', link: '/' }, { label: 'Аудио-теги', link: '/audio_tags' }, { label: tab.title }];
    });
    protected readonly coverSrc = computed(() => {
        const info = this.info();

        return null !== info && info.tags.hasPicture ? `${this.api.coverUrl(info.id)}?v=${this.coverVersion()}` : null;
    });
    protected readonly coverPreview = computed(() => (this.removePicture() ? null : (this.coverObjectUrl() ?? this.coverSrc())));
    protected readonly coverDownloadUrl = computed(() => {
        const info = this.info();

        return null !== info && info.tags.hasPicture ? this.api.coverUrl(info.id) : null;
    });
    protected readonly downloadUrl = computed(() => {
        const info = this.info();

        return null === info ? null : this.api.downloadUrl(info.id);
    });
    protected readonly size = formatBytes;

    ngOnInit(): void {
        this.route.data.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((data) => {
            this.tabIndex.set(AudioTags.tabIndex(data));
        });

        this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
            const id = params.get('id');

            if (null !== id) {
                void this.load(id);
            }
        });
    }

    protected selectTab(index: number): void {
        const tab = AUDIO_TAGS_TABS[index];

        if (!tab || index === this.tabIndex()) {
            return;
        }

        this.tabIndex.set(index);

        const info = this.info();
        const path = 'tool' === tab.id && null !== info ? `/audio_tags/${info.id}` : tab.path;

        void this.router.navigateByUrl(path);
    }

    protected async create(): Promise<void> {
        const file = this.sourceFiles()[0] ?? null;
        const url = this.sourceUrl().trim();

        if (null === file && '' === url) {
            this.error.set('Выберите файл или укажите ссылку.');
            return;
        }

        this.loading.set(true);
        this.error.set(null);
        this.notice.set(null);

        try {
            const info = await this.api.create(file, url);

            this.setInfo(info);
            this.notice.set('Файл загружен. Можно редактировать теги.');
            void this.router.navigate(['/audio_tags', info.id], { replaceUrl: true });
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    protected async update(): Promise<void> {
        const info = this.info();

        if (null === info) {
            return;
        }

        this.saving.set(true);
        this.error.set(null);
        this.notice.set(null);

        try {
            const updated = await this.api.update(info.id, {
                title: this.title(),
                albumArtist: this.albumArtist(),
                artist: this.artist(),
                album: this.album(),
                year: this.year(),
                trackNumber: this.trackNumber(),
                urlUser: this.urlUser(),
                genre: this.genre(),
                comment: this.comment(),
                composer: this.composer(),
                discNumber: this.discNumber(),
                copyright: this.copyright(),
                lyrics: this.lyrics(),
                bpm: this.bpm(),
                isrc: this.isrc(),
                removeOtherTags: this.removeOtherTags(),
                removePicture: this.removePicture(),
                picture: this.coverFiles()[0] ?? null,
                pictureUrl: this.coverUrl(),
            });

            this.coverVersion.update((value) => value + 1);
            this.setInfo(updated);
            this.notice.set('Теги сохранены.');
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.saving.set(false);
        }
    }

    protected async reset(): Promise<void> {
        if (this.resetting()) {
            return;
        }

        this.resetting.set(true);

        try {
            const info = this.info();

            if (null !== info) {
                try {
                    await this.api.delete(info.id);
                } catch {
                    // Временный файл всё равно удалится по расписанию.
                }
            }

            this.info.set(null);
            this.sourceFiles.set([]);
            this.sourceUrl.set('');
            this.error.set(null);
            this.notice.set(null);
            this.clearFields();
            void this.router.navigateByUrl('/audio_tags', { replaceUrl: true });
        } finally {
            this.resetting.set(false);
            this.pendingReset.set(false);
        }
    }

    protected setSourceFiles(files: File[]): void {
        this.sourceFiles.set(files);
        this.error.set(null);
    }

    protected setSourceUrl(url: string): void {
        this.sourceUrl.set(url);
        this.error.set(null);
    }

    protected setCoverFiles(files: File[]): void {
        this.coverFiles.set(files);
        this.revokeCover();

        const file = files[0];

        if (file && 'function' === typeof URL.createObjectURL) {
            this.coverObjectUrl.set(URL.createObjectURL(file));
        }
    }

    protected setCoverUrl(url: string): void {
        this.coverUrl.set(url);
    }

    protected onGenreInput(event: Event): void {
        this.genreQuery.set((event.target as HTMLInputElement).value);
    }

    protected fillFromFilename(): void {
        const info = this.info();

        if (null === info) {
            return;
        }

        const parsed = parseAudioFileName(info.originalName);
        const filled: string[] = [];

        if (parsed.title && '' === this.title().trim()) {
            this.title.set(parsed.title);
            filled.push('название');
        }

        if (parsed.artist && '' === this.artist().trim()) {
            this.artist.set(parsed.artist);
            filled.push('исполнитель');
        }

        if (parsed.album && '' === this.album().trim()) {
            this.album.set(parsed.album);
            filled.push('альбом');
        }

        if (parsed.trackNumber && '' === this.trackNumber().trim()) {
            this.trackNumber.set(parsed.trackNumber);
            filled.push('номер трека');
        }

        if (0 === filled.length) {
            this.error.set('В имени файла не удалось найти новые теги.');

            return;
        }

        this.error.set(null);
        this.notice.set(`Заполнено из имени файла: ${filled.join(', ')}.`);
    }

    protected async clearTags(): Promise<void> {
        const info = this.info();

        if (null === info) {
            return;
        }

        this.clearing.set(true);
        this.error.set(null);
        this.notice.set(null);

        try {
            const updated = await this.api.clear(info.id);

            this.coverVersion.update((value) => value + 1);
            this.setInfo(updated);
            this.notice.set('Все теги удалены.');
        } catch (error) {
            this.error.set(describeError(error));
        } finally {
            this.clearing.set(false);
            this.pendingClear.set(false);
        }
    }

    private revokeCover(): void {
        const url = this.coverObjectUrl();

        if (url && 'function' === typeof URL.revokeObjectURL) {
            URL.revokeObjectURL(url);
        }

        this.coverObjectUrl.set(null);
    }

    protected bitrate(value: AudioTagsInfoResponse): string {
        return null == value.bitrate ? '—' : `${Math.round(value.bitrate / 1000)} кбит/с`;
    }

    protected sampleRate(value: AudioTagsInfoResponse): string {
        return null == value.sampleRate ? '—' : `${(value.sampleRate / 1000).toFixed(1)} кГц`;
    }

    protected duration(value: AudioTagsInfoResponse): string {
        if (null == value.duration) {
            return '—';
        }

        const total = Math.max(0, Math.round(value.duration));
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;

        return hours > 0
            ? `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
            : `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    private async load(id: string): Promise<void> {
        if (this.info()?.id === id) {
            return;
        }

        this.loading.set(true);
        this.error.set(null);
        this.notice.set(null);

        try {
            this.setInfo(await this.api.show(id));
        } catch (error) {
            this.info.set(null);
            this.clearFields();
            this.error.set(describeError(error));
        } finally {
            this.loading.set(false);
        }
    }

    private setInfo(info: AudioTagsInfoResponse): void {
        this.info.set(info);

        this.title.set(info.tags.title ?? '');
        this.albumArtist.set(info.tags.albumArtist ?? '');
        this.artist.set(info.tags.artist ?? '');
        this.album.set(info.tags.album ?? '');
        this.year.set(info.tags.year ?? '');
        this.trackNumber.set(info.tags.trackNumber ?? '');
        this.urlUser.set(info.tags.urlUser ?? '');
        this.genre.set(info.tags.genre ?? '');
        this.genreQuery.set('');
        this.comment.set(info.tags.comment ?? '');
        this.composer.set(info.tags.composer ?? '');
        this.discNumber.set(info.tags.discNumber ?? '');
        this.copyright.set(info.tags.copyright ?? '');
        this.lyrics.set(info.tags.lyrics ?? '');
        this.bpm.set(info.tags.bpm ?? '');
        this.isrc.set(info.tags.isrc ?? '');
        this.coverFiles.set([]);
        this.coverUrl.set('');
        this.revokeCover();
        this.removeOtherTags.set(false);
        this.removePicture.set(false);
        this.pendingClear.set(false);
        this.pendingReset.set(false);
    }

    private clearFields(): void {
        this.title.set('');
        this.albumArtist.set('');
        this.artist.set('');
        this.album.set('');
        this.year.set('');
        this.trackNumber.set('');
        this.urlUser.set('');
        this.genre.set('');
        this.genreQuery.set('');
        this.comment.set('');
        this.composer.set('');
        this.discNumber.set('');
        this.copyright.set('');
        this.lyrics.set('');
        this.bpm.set('');
        this.isrc.set('');
        this.removeOtherTags.set(false);
        this.removePicture.set(false);
        this.coverFiles.set([]);
        this.coverUrl.set('');
        this.revokeCover();
        this.pendingClear.set(false);
        this.pendingReset.set(false);
    }

    private static tabIndex(data: Data): number {
        const index = AUDIO_TAGS_TABS.findIndex((tab) => tab.id === data['tab']);

        return index < 0 ? 0 : index;
    }
}
