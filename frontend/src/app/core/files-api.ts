import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { API_URL } from './api-url';
import { Api } from '../api/generated/api';
import { deleteApiFilesDelete, getApiFilesList, getApiFilesShow, getApiFilesStatistics } from '../api/generated/functions';
import { ArchiveListResponse, FileFilterQuery, FileListResponse, FileResponse, FileStatisticsResponse } from '../api/generated/models';

export interface FileUpload {
    file?: File | null;
    url?: string | null;
    description: string;
    tags: string[];
    password?: string | null;
    captchaToken?: string | null;
}

export interface FileChanges {
    description?: string | null;
    tags?: string[] | null;
    password?: string | null;
    file?: File | null;
    url?: string | null;
    hidden?: boolean | null;
    captchaToken?: string | null;
}

@Injectable({ providedIn: 'root' })
export class FilesApi {
    private readonly api = inject(Api);
    private readonly http = inject(HttpClient);
    private readonly apiUrl = inject(API_URL);

    list(params: {
        filter?: FileFilterQuery;
        page?: number;
        perPage?: number;
        sort?: 'newest' | 'oldest' | 'name' | 'views' | 'relevance';
    }): Promise<FileListResponse> {
        return this.api.invoke(getApiFilesList, params);
    }

    statistics(): Promise<FileStatisticsResponse> {
        return this.api.invoke(getApiFilesStatistics, {});
    }

    show(id: number, password?: string): Promise<FileResponse> {
        if (password) {
            return firstValueFrom(
                this.http.get<FileResponse>(`${this.apiUrl}/files/${id}`, {
                    headers: { 'X-File-Password': password },
                }),
            );
        }

        return this.api.invoke(getApiFilesShow, { id: String(id) });
    }

    async delete(id: number): Promise<void> {
        await this.api.invoke(deleteApiFilesDelete, { id: String(id) });
    }

    create(input: FileUpload): Promise<FileResponse> {
        return firstValueFrom(this.http.post<FileResponse>(`${this.apiUrl}/files`, this.formData(input)));
    }

    update(id: number, changes: FileChanges): Promise<FileResponse> {
        if (changes.file || changes.url) {
            return firstValueFrom(this.http.patch<FileResponse>(`${this.apiUrl}/files/${id}`, this.formData(changes)));
        }

        return firstValueFrom(
            this.http.patch<FileResponse>(`${this.apiUrl}/files/${id}`, {
                description: changes.description,
                tags: changes.tags,
                password: changes.password,
                hidden: changes.hidden,
            }),
        );
    }

    download(id: number, password?: string): Promise<Blob> {
        return firstValueFrom(
            this.http.get(`${this.apiUrl}/files/${id}/download`, {
                responseType: 'blob',
                headers: password ? { 'X-File-Password': password } : {},
            }),
        );
    }

    archive(id: number, password?: string): Promise<ArchiveListResponse> {
        return firstValueFrom(
            this.http.get<ArchiveListResponse>(`${this.apiUrl}/files/${id}/archive`, {
                headers: password ? { 'X-Archive-Password': password } : {},
            }),
        );
    }

    archiveEntry(id: number, path: string, password?: string): Promise<Blob> {
        return firstValueFrom(
            this.http.get(`${this.apiUrl}/files/${id}/archive/entry`, {
                params: { path },
                responseType: 'blob',
                headers: password ? { 'X-Archive-Password': password } : {},
            }),
        );
    }

    private formData(input: FileUpload | FileChanges): FormData {
        const data = new FormData();

        if (input.file) {
            data.append('file', input.file, input.file.name);
        }

        if (input.url !== null && input.url !== undefined && input.url !== '') {
            data.append('url', input.url);
        }

        if (input.description !== null && input.description !== undefined) {
            data.append('description', input.description);
        }

        if (input.tags !== null && input.tags !== undefined) {
            if (input.tags.length === 0) {
                // An empty `tags[]` field tells the API to clear all tags.
                data.append('tags[]', '');
            } else {
                for (const tag of input.tags) {
                    data.append('tags[]', tag);
                }
            }
        }

        if (input.password !== null && input.password !== undefined) {
            data.append('password', input.password);
        }

        if (input.captchaToken !== null && input.captchaToken !== undefined && input.captchaToken !== '') {
            data.append('captchaToken', input.captchaToken);
        }

        return data;
    }
}
