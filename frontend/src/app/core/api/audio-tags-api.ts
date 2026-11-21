import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { AudioTagsInfoResponse } from '../../api/generated/models';
import { API_URL } from './api-url';

export interface AudioTagsUpdate {
    title: string;
    albumArtist: string;
    artist: string;
    album: string;
    year: string;
    trackNumber: string;
    urlUser: string;
    genre: string;
    comment: string;
    composer: string;
    discNumber: string;
    copyright: string;
    lyrics: string;
    bpm: string;
    isrc: string;
    removeOtherTags: boolean;
    removePicture: boolean;
    picture?: File | null;
    pictureUrl?: string | null;
}

@Injectable({ providedIn: 'root' })
export class AudioTagsApi {
    private readonly http = inject(HttpClient);
    private readonly apiUrl = inject(API_URL);

    show(id: string): Promise<AudioTagsInfoResponse> {
        return firstValueFrom(this.http.get<AudioTagsInfoResponse>(`${this.apiUrl}/audio-tags/${id}`));
    }

    create(file: File | null, url: string): Promise<AudioTagsInfoResponse> {
        const data = new FormData();

        if (file) {
            data.append('files[]', file, file.name);
        }

        if ('' !== url) {
            data.append('url', url);
        }

        return firstValueFrom(this.http.post<AudioTagsInfoResponse>(`${this.apiUrl}/audio-tags`, data));
    }

    update(id: string, input: AudioTagsUpdate): Promise<AudioTagsInfoResponse> {
        const data = new FormData();

        data.append('title', input.title);
        data.append('albumArtist', input.albumArtist);
        data.append('artist', input.artist);
        data.append('album', input.album);
        data.append('year', input.year);
        data.append('trackNumber', input.trackNumber);
        data.append('urlUser', input.urlUser);
        data.append('genre', input.genre);
        data.append('comment', input.comment);
        data.append('composer', input.composer);
        data.append('discNumber', input.discNumber);
        data.append('copyright', input.copyright);
        data.append('lyrics', input.lyrics);
        data.append('bpm', input.bpm);
        data.append('isrc', input.isrc);

        if (input.removeOtherTags) {
            data.append('removeOtherTags', '1');
        }

        if (input.removePicture) {
            data.append('removePicture', '1');
        } else {
            if (input.picture) {
                data.append('picture', input.picture, input.picture.name);
            }

            if (input.pictureUrl && '' !== input.pictureUrl.trim()) {
                data.append('pictureUrl', input.pictureUrl.trim());
            }
        }

        return firstValueFrom(this.http.post<AudioTagsInfoResponse>(`${this.apiUrl}/audio-tags/${id}`, data));
    }

    async delete(id: string): Promise<void> {
        await firstValueFrom(this.http.delete(`${this.apiUrl}/audio-tags/${id}`));
    }

    clear(id: string): Promise<AudioTagsInfoResponse> {
        return firstValueFrom(this.http.post<AudioTagsInfoResponse>(`${this.apiUrl}/audio-tags/${id}/clear`, null));
    }

    downloadUrl(id: string): string {
        return `${this.apiUrl}/audio-tags/${id}/download`;
    }

    coverUrl(id: string): string {
        return `${this.apiUrl}/audio-tags/${id}/cover`;
    }
}
