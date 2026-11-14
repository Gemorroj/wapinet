import { DEFAULT_HASH_SELECTION, HASH_ALGORITHMS, HASH_FILE_LIMIT, HashError, crc32, crc32c, hashBytes, hashFile, hashText } from './hash-codec';

const enc = (value: string): Uint8Array => new TextEncoder().encode(value);

const ABC = {
    md5: '900150983cd24fb0d6963f7d28e17f72',
    sha1: 'a9993e364706816aba3e25717850c26c9cd0d89d',
    sha224: '23097d223405d8228642a477bda255b32aadbce4bda0b3f7e36c9da7',
    sha256: 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
    sha384: 'cb00753f45a35e8bb5a03d699ac65007272c32ab0eded1631a8b605a43ff5bed8086072ba1e7cc2358baeca134c825a7',
    sha512: 'ddaf35a193617abacc417349ae20413112e6fa4e89a97ea20a9eeee64b55d39a2192992a274fc1a836ba3c23a3feebbd454d4423643ce80e2a9ac94fa54ca49f',
    'sha512-224': '4634270f707b6a54daae7530460842e20e37ed265ceee9a43e8924aa',
    'sha512-256': '53048e2681941ef99b2e29b76b4c7dabe4c2d0c634fc6d46e0e2f13107e7af23',
    'sha3-224': 'e642824c3f8cf24ad09234ee7d3c766fc9a3a5168d0c94ad73b46fdf',
    'sha3-256': '3a985da74fe225b2045c172d6bd390bd855f086e3e9d525b46bfe24511431532',
    'sha3-384': 'ec01498288516fc926459f58e2c6ad8df9b473cb0fc08c2596da7cf0e49be4b298d88cea927ac7f539f1edf228376d25',
    'sha3-512': 'b751850b1a57168a5693cd924b6b096e08f621827444f70d884f5d0240d2712e10e116e9192af3c91a7ec57647e3934057340b4cf408d5a56592f8274eec53f0',
    ripemd160: '8eb208f7e05d987a9b044a8e98c6b087f15a0bfc',
    crc32: '352441c2',
    crc32c: '364b3fb7',
};

describe('hashBytes', () => {
    it('matches the reference vectors for every algorithm', () => {
        const results = hashBytes(
            enc('abc'),
            HASH_ALGORITHMS.map((item) => item.id),
        );

        expect(Object.fromEntries(results.map((item) => [item.id, item.value]))).toEqual(ABC);
    });

    it('computes only the requested algorithms in canonical order', () => {
        expect(hashBytes(enc('abc'), ['crc32', 'sha256']).map((item) => item.id)).toEqual(['sha256', 'crc32']);
    });

    it('returns nothing for an empty selection', () => {
        expect(hashBytes(enc('abc'), [])).toEqual([]);
    });

    it('uses the default set', () => {
        expect(hashBytes(enc('abc')).map((item) => item.id)).toEqual([...DEFAULT_HASH_SELECTION]);
    });
});

describe('crc32', () => {
    it('matches the reference vectors', () => {
        expect(crc32(enc(''))).toBe('00000000');
        expect(crc32(enc('abc'))).toBe('352441c2');
        expect(crc32(enc('123456789'))).toBe('cbf43926');
        expect(crc32(enc('Привет, мир!'))).toBe('391651bc');
    });
});

describe('crc32c', () => {
    it('matches the reference vectors', () => {
        expect(crc32c(enc(''))).toBe('00000000');
        expect(crc32c(enc('abc'))).toBe('364b3fb7');
        expect(crc32c(enc('123456789'))).toBe('e3069283');
    });
});

describe('hashText', () => {
    it('hashes text as UTF-8', () => {
        expect(hashText('Привет, мир!', ['md5', 'sha256']).map((item) => item.value)).toEqual([
            'c446a2994f35689482651b7c7ba8b56c',
            '9a0af011605a6ab9022f248fb797064a42782210ac91e834348b3ef089313e5f',
        ]);
    });
});

describe('hashFile', () => {
    it('hashes file contents', async () => {
        const results = await hashFile(new File(['abc'], 'abc.txt'), ['sha1', 'crc32']);

        expect(results.map((item) => item.value)).toEqual([ABC.sha1, ABC.crc32]);
    });

    it('rejects files that are too big for memory', async () => {
        const file = { size: HASH_FILE_LIMIT + 1, arrayBuffer: () => Promise.resolve(new ArrayBuffer(0)) } as File;

        await expect(hashFile(file)).rejects.toBeInstanceOf(HashError);
    });
});
