export interface CapabilityRow {
    readonly label: string;
    readonly source: string;
    readonly value: string;
}

export interface Capability {
    readonly label: string;
    readonly probe: () => boolean;
    readonly source: string;
}

const CAPABILITIES: readonly Capability[] = [
    { label: 'WebAssembly', probe: () => 'WebAssembly' in window, source: 'window.WebAssembly' },
    { label: 'WebGPU', probe: () => 'gpu' in navigator, source: 'navigator.gpu' },
    { label: 'Service Worker', probe: () => 'serviceWorker' in navigator, source: 'navigator.serviceWorker' },
    { label: 'Web Push', probe: () => 'PushManager' in window, source: 'window.PushManager' },
    { label: 'File System Access', probe: () => 'showOpenFilePicker' in window, source: 'window.showOpenFilePicker' },
    { label: 'Web Share', probe: () => 'share' in navigator, source: 'navigator.share' },
    { label: 'Web Bluetooth', probe: () => 'bluetooth' in navigator, source: 'navigator.bluetooth' },
    { label: 'Web USB', probe: () => 'usb' in navigator, source: 'navigator.usb' },
    { label: 'Web Serial', probe: () => 'serial' in navigator, source: 'navigator.serial' },
    { label: 'Web NFC', probe: () => 'NDEFReader' in window, source: 'window.NDEFReader' },
    { label: 'Wake Lock', probe: () => 'wakeLock' in navigator, source: 'navigator.wakeLock' },
    { label: 'WebAuthn', probe: () => 'PublicKeyCredential' in window, source: 'window.PublicKeyCredential' },
    { label: 'Web Codecs', probe: () => 'VideoEncoder' in window, source: 'window.VideoEncoder' },
    { label: 'SharedArrayBuffer', probe: () => 'SharedArrayBuffer' in window, source: 'window.SharedArrayBuffer' },
    { label: 'AVIF', probe: avifSupported, source: "HTMLCanvasElement.toDataURL('image/avif')" },
];

export function capabilityRows(capabilities: readonly Capability[] = CAPABILITIES): CapabilityRow[] {
    return capabilities.map(({ label, probe, source }) => ({
        label,
        source,
        value: probe() ? 'Да' : 'Нет',
    }));
}

function avifSupported(): boolean {
    try {
        return document.createElement('canvas').toDataURL('image/avif').startsWith('data:image/avif');
    } catch {
        return false;
    }
}
