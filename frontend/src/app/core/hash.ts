import { md5, ripemd160, sha1 } from '@noble/hashes/legacy.js';
import { sha224, sha256, sha384, sha512, sha512_224, sha512_256 } from '@noble/hashes/sha2.js';
import { sha3_224, sha3_256, sha3_384, sha3_512 } from '@noble/hashes/sha3.js';

export type HashAlgorithmId =
    | 'md5'
    | 'sha1'
    | 'sha224'
    | 'sha256'
    | 'sha384'
    | 'sha512'
    | 'sha512-224'
    | 'sha512-256'
    | 'sha3-224'
    | 'sha3-256'
    | 'sha3-384'
    | 'sha3-512'
    | 'ripemd160'
    | 'crc32'
    | 'crc32c';

export interface HashAlgorithm {
    readonly id: HashAlgorithmId;
    readonly label: string;
    readonly hash: (bytes: Uint8Array) => string;
}

export interface HashResult {
    readonly id: HashAlgorithmId;
    readonly label: string;
    readonly value: string;
}

export class HashError extends Error {}

const hex =
    (digest: (bytes: Uint8Array) => Uint8Array) =>
    (bytes: Uint8Array): string =>
        toHex(digest(bytes));

export const HASH_ALGORITHMS: readonly HashAlgorithm[] = [
    { id: 'md5', label: 'MD5', hash: hex(md5) },
    { id: 'sha1', label: 'SHA-1', hash: hex(sha1) },
    { id: 'sha224', label: 'SHA-224', hash: hex(sha224) },
    { id: 'sha256', label: 'SHA-256', hash: hex(sha256) },
    { id: 'sha384', label: 'SHA-384', hash: hex(sha384) },
    { id: 'sha512', label: 'SHA-512', hash: hex(sha512) },
    { id: 'sha512-224', label: 'SHA-512/224', hash: hex(sha512_224) },
    { id: 'sha512-256', label: 'SHA-512/256', hash: hex(sha512_256) },
    { id: 'sha3-224', label: 'SHA3-224', hash: hex(sha3_224) },
    { id: 'sha3-256', label: 'SHA3-256', hash: hex(sha3_256) },
    { id: 'sha3-384', label: 'SHA3-384', hash: hex(sha3_384) },
    { id: 'sha3-512', label: 'SHA3-512', hash: hex(sha3_512) },
    { id: 'ripemd160', label: 'RIPEMD-160', hash: hex(ripemd160) },
    { id: 'crc32', label: 'CRC32', hash: crc32 },
    { id: 'crc32c', label: 'CRC32C', hash: crc32c },
];

/** Набор по умолчанию — то, что нужно чаще всего. */
export const DEFAULT_HASH_SELECTION: readonly HashAlgorithmId[] = ['md5', 'sha256', 'sha512'];

/** Файл читается в память целиком, поэтому большие файлы не берём. */
export const HASH_FILE_LIMIT = 256 * 1024 * 1024;

const CRC32_TABLE = buildCrc32Table(0xedb88320);
const CRC32C_TABLE = buildCrc32Table(0x82f63b78);

function buildCrc32Table(polynomial: number): Uint32Array {
    const table = new Uint32Array(256);

    for (let index = 0; index < 256; index++) {
        let value = index;

        for (let bit = 0; bit < 8; bit++) {
            value = 0 !== (value & 1) ? polynomial ^ (value >>> 1) : value >>> 1;
        }

        table[index] = value >>> 0;
    }

    return table;
}

export function hashText(text: string, algorithms: readonly HashAlgorithmId[] = DEFAULT_HASH_SELECTION): HashResult[] {
    return hashBytes(new TextEncoder().encode(text), algorithms);
}

export async function hashFile(file: File, algorithms: readonly HashAlgorithmId[] = DEFAULT_HASH_SELECTION): Promise<HashResult[]> {
    if (file.size > HASH_FILE_LIMIT) {
        throw new HashError('Файл больше 256 МБ — браузер не сможет посчитать хэш без загрузки в память.');
    }

    return hashBytes(new Uint8Array(await file.arrayBuffer()), algorithms);
}

export function hashBytes(bytes: Uint8Array, algorithms: readonly HashAlgorithmId[] = DEFAULT_HASH_SELECTION): HashResult[] {
    return HASH_ALGORITHMS.filter((algorithm) => algorithms.includes(algorithm.id)).map((algorithm) => ({
        id: algorithm.id,
        label: algorithm.label,
        value: algorithm.hash(bytes),
    }));
}

export function crc32(bytes: Uint8Array): string {
    return crc32With(CRC32_TABLE, bytes);
}

export function crc32c(bytes: Uint8Array): string {
    return crc32With(CRC32C_TABLE, bytes);
}

function crc32With(table: Uint32Array, bytes: Uint8Array): string {
    let crc = 0xffffffff;

    for (const byte of bytes) {
        crc = table[(crc ^ byte) & 0xff] ^ (crc >>> 8);
    }

    return ((crc ^ 0xffffffff) >>> 0).toString(16).padStart(8, '0');
}

function toHex(bytes: Uint8Array): string {
    return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
}
