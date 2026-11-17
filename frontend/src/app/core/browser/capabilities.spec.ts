import { capabilityRows } from './capabilities';

describe('capabilityRows', () => {
    it('marks available and missing APIs', () => {
        const rows = capabilityRows([
            { label: 'Есть', probe: () => true, source: 'window.available' },
            { label: 'Нет', probe: () => false, source: 'window.missing' },
        ]);

        expect(rows).toEqual([
            { label: 'Есть', source: 'window.available', value: 'Да' },
            { label: 'Нет', source: 'window.missing', value: 'Нет' },
        ]);
    });

    it('probes the platform capabilities', () => {
        const rows = capabilityRows();

        expect(rows.map((row) => row.label)).toContain('WebAssembly');
        expect(rows.map((row) => row.label)).toContain('WebGPU');
        expect(rows.every((row) => 'Да' === row.value || 'Нет' === row.value)).toBe(true);
    });

    it('explains the AVIF check through the canvas encoder', () => {
        const avif = capabilityRows().find((row) => 'AVIF' === row.label);

        expect(avif?.source).toContain('image/avif');
    });
});
