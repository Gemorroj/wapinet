import { TestBed } from '@angular/core/testing';

import { Api } from '../api/generated/api';
import { Auth } from './auth';

describe('Auth', () => {
    const invoke = vi.fn();

    beforeEach(() => {
        invoke.mockReset();
        localStorage.clear();

        TestBed.configureTestingModule({
            providers: [{ provide: Api, useValue: { invoke } }],
        });
    });

    it('treats ROLE_SUPER_ADMIN as an admin', async () => {
        invoke.mockResolvedValueOnce({
            token: 'token',
            user: { id: 1, username: 'admin', email: 'admin@example.com', roles: ['ROLE_SUPER_ADMIN', 'ROLE_USER'] },
        });

        const auth = TestBed.inject(Auth);

        await auth.login('admin', 'secret');

        expect(auth.isAdmin()).toBe(true);
    });

    it('does not treat a regular user as an admin', async () => {
        invoke.mockResolvedValueOnce({
            token: 'token',
            user: { id: 1, username: 'user', email: 'user@example.com', roles: ['ROLE_USER'] },
        });

        const auth = TestBed.inject(Auth);

        await auth.login('user', 'secret');

        expect(auth.isAdmin()).toBe(false);
    });
});
