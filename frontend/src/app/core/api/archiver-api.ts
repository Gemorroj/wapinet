import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { API_URL } from './api-url';
import { Api } from '../../api/generated/api';
import { deleteApiArchiverDelete, deleteApiArchiverRemove, getApiArchiverShow } from '../../api/generated/functions';
import { ArchiveResponse } from '../../api/generated/models';

export interface ArchiveSource {
    files?: File[];
    url?: string | null;
}

@Injectable({ providedIn: 'root' })
export class ArchiverApi {
    private readonly api = inject(Api);
    private readonly http = inject(HttpClient);
    private readonly apiUrl = inject(API_URL);

    create(source: ArchiveSource): Promise<ArchiveResponse> {
        return firstValueFrom(this.http.post<ArchiveResponse>(`${this.apiUrl}/archiver/create`, this.formData(source)));
    }

    extract(source: ArchiveSource): Promise<ArchiveResponse> {
        return firstValueFrom(this.http.post<ArchiveResponse>(`${this.apiUrl}/archiver/extract`, this.formData(source)));
    }

    show(archive: string): Promise<ArchiveResponse> {
        return this.api.invoke(getApiArchiverShow, { archive });
    }

    add(archive: string, source: ArchiveSource): Promise<ArchiveResponse> {
        return firstValueFrom(this.http.post<ArchiveResponse>(`${this.apiUrl}/archiver/${encodeURIComponent(archive)}/files`, this.formData(source)));
    }

    remove(archive: string, path: string): Promise<ArchiveResponse> {
        return this.api.invoke(deleteApiArchiverDelete, { archive, path });
    }

    async removeArchive(archive: string): Promise<void> {
        await this.api.invoke(deleteApiArchiverRemove, { archive });
    }

    download(archive: string): Promise<Blob> {
        return firstValueFrom(this.http.get(`${this.apiUrl}/archiver/${encodeURIComponent(archive)}/download`, { responseType: 'blob' }));
    }

    downloadFile(archive: string, path: string): Promise<Blob> {
        return firstValueFrom(
            this.http.get(`${this.apiUrl}/archiver/${encodeURIComponent(archive)}/file`, {
                params: { path },
                responseType: 'blob',
            }),
        );
    }

    private formData(source: ArchiveSource): FormData {
        const data = new FormData();

        for (const file of source.files ?? []) {
            data.append('files[]', file, file.name);
        }

        if (source.url) {
            data.append('url', source.url);
        }

        return data;
    }
}
