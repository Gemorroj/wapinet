import { permissionLabel, permissionRows } from './permissions';

describe('permissionLabel', () => {
    it('translates known states', () => {
        expect(permissionLabel('granted')).toBe('Разрешено');
        expect(permissionLabel('denied')).toBe('Запрещено');
        expect(permissionLabel('prompt')).toBe('Спросит при использовании');
    });

    it('keeps an unknown state as is', () => {
        expect(permissionLabel('unknown')).toBe('unknown');
    });

    it('treats a missing state as unsupported', () => {
        expect(permissionLabel(undefined)).toBe('Не поддерживается');
    });
});

describe('permissionRows', () => {
    it('returns null without the Permissions API', async () => {
        expect(await permissionRows(null)).toBeNull();
    });

    it('reports granted permissions', async () => {
        const rows = await permissionRows(async () => ({ state: 'granted' }));

        expect(rows).toHaveLength(13);
        expect(rows?.every((row) => 'Разрешено' === row.value)).toBe(true);
    });

    it('reports a rejected query as unsupported', async () => {
        const rows = await permissionRows(async ({ name }) => {
            if ('camera' === name) {
                throw new Error('unsupported');
            }

            return { state: 'prompt' };
        });

        expect(rows?.find((row) => 'Камера' === row.label)?.value).toBe('Не поддерживается');
        expect(rows?.find((row) => 'Микрофон' === row.label)?.value).toBe('Спросит при использовании');
    });

    it('describes the query descriptor in the source hint', async () => {
        const rows = await permissionRows(async () => ({ state: 'denied' }));

        expect(rows?.[0]?.source).toContain("name: 'geolocation'");
    });
});
