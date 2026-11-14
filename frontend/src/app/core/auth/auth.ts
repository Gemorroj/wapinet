import { HttpErrorResponse } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';

import { Api } from '../../api/generated/api';
import { getApiMe, postApiAuthLogin, postApiAuthLogout } from '../../api/generated/functions';
import { UserResponse } from '../../api/generated/models';

const TOKEN_STORAGE_KEY = 'wapinet.api.token';

function readStoredToken(): string | null {
    try {
        return localStorage.getItem(TOKEN_STORAGE_KEY);
    } catch {
        return null;
    }
}

function storeToken(token: string | null): void {
    try {
        if (token === null) {
            localStorage.removeItem(TOKEN_STORAGE_KEY);
        } else {
            localStorage.setItem(TOKEN_STORAGE_KEY, token);
        }
    } catch {
        // Storage can be unavailable (private mode, disabled cookies).
    }
}

@Injectable({ providedIn: 'root' })
export class Auth {
    private readonly api = inject(Api);
    private readonly tokenState = signal<string | null>(readStoredToken());
    private readonly userState = signal<UserResponse | null>(null);
    private loggingOut = false;

    readonly token = this.tokenState.asReadonly();
    readonly user = this.userState.asReadonly();
    readonly authenticated = computed(() => this.tokenState() !== null);
    readonly isAdmin = computed(() => {
        const roles = this.userState()?.roles ?? [];

        return roles.includes('ROLE_ADMIN') || roles.includes('ROLE_SUPER_ADMIN');
    });

    async login(username: string, password: string): Promise<void> {
        const response = await this.api.invoke(postApiAuthLogin, {
            body: { username, password },
        });

        storeToken(response.token);
        this.tokenState.set(response.token);
        this.userState.set(response.user);
    }

    async loadMe(): Promise<void> {
        if (this.tokenState() === null) {
            return;
        }

        try {
            this.userState.set(await this.api.invoke(getApiMe));
        } catch (error) {
            if (error instanceof HttpErrorResponse && error.status === 401) {
                this.clearSession();
            }
        }
    }

    clearSession(): void {
        storeToken(null);
        this.tokenState.set(null);
        this.userState.set(null);
    }

    async logout(): Promise<void> {
        if (this.loggingOut) {
            return;
        }

        this.loggingOut = true;

        try {
            if (this.tokenState() !== null) {
                // Revoke the token on the server; a stale token may already be rejected.
                await this.api.invoke(postApiAuthLogout);
            }
        } catch {
            // The local session must be cleared even if the server call fails.
        } finally {
            this.clearSession();
            this.loggingOut = false;
        }
    }
}
