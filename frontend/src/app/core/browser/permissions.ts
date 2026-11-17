export interface PermissionRow {
    readonly label: string;
    readonly source: string;
    readonly value: string;
}

export interface PermissionStatusLike {
    readonly state: string;
}

export type PermissionQuery = (descriptor: { name: string }) => Promise<PermissionStatusLike>;

const PERMISSIONS: ReadonlyArray<readonly [label: string, name: string]> = [
    ['Геолокация', 'geolocation'],
    ['Уведомления', 'notifications'],
    ['Камера', 'camera'],
    ['Микрофон', 'microphone'],
    ['Чтение буфера обмена', 'clipboard-read'],
    ['Запись буфера обмена', 'clipboard-write'],
    ['Постоянное хранилище', 'persistent-storage'],
    ['Фоновая синхронизация', 'background-sync'],
    ['Акселерометр', 'accelerometer'],
    ['Гироскоп', 'gyroscope'],
    ['Магнитометр', 'magnetometer'],
    ['MIDI', 'midi'],
    ['Платёжный обработчик', 'payment-handler'],
];

const STATES: Record<string, string> = {
    denied: 'Запрещено',
    granted: 'Разрешено',
    prompt: 'Спросит при использовании',
};

export function permissionLabel(state: string | null | undefined): string {
    if (!state) {
        return 'Не поддерживается';
    }

    return STATES[state] ?? state;
}

export async function permissionRows(query: PermissionQuery | null = browserQuery()): Promise<PermissionRow[] | null> {
    if (null === query) {
        return null;
    }

    return Promise.all(
        PERMISSIONS.map(async ([label, name]) => ({
            label,
            source: `navigator.permissions.query({ name: '${name}' })`,
            value: await queryState(query, name),
        })),
    );
}

async function queryState(query: PermissionQuery, name: string): Promise<string> {
    try {
        return permissionLabel((await query({ name })).state);
    } catch {
        return 'Не поддерживается';
    }
}

function browserQuery(): PermissionQuery | null {
    const permissions = navigator.permissions as unknown as { query?: PermissionQuery } | undefined;

    return 'function' === typeof permissions?.query ? permissions.query.bind(permissions) : null;
}
